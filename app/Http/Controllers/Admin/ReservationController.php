<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ReservationApprovedMail;
use App\Mail\ReservationPaymentMail;
use App\Mail\ReservationRejectedMail;
use App\Models\Playground;
use App\Models\Reservation;
use App\Services\ReservationPdfService;
use App\Services\ReservationService;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReservationController extends Controller
{
    use JsonResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $reservations = Reservation::query()
            ->with(['playground.area'])
            ->when($request->query('status'), fn($query, $status) => $query->where('status', $status))
            ->when($request->query('playground_id'), fn($query, $id) => $query->where('playground_id', $id))
            ->when($request->query('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%")
                        ->orWhere('variable_symbol', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return $this->successResponse($reservations);
    }

    /**
     * Manually records a reservation an admin already agreed outside the
     * system (e.g. a signed contract) so its slot is blocked here too.
     * Deliberately skips every public-booking eligibility rule (duration
     * cap, booking horizon, opening hours, no-past-dates) - none of those
     * are data-integrity concerns, just self-service UX guardrails that
     * don't apply to a trusted admin recording a real external agreement.
     * The one check that's never skipped is slot overlap, since that would
     * corrupt availability for everyone else.
     */
    public function storeManual(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'playground_id' => 'required|exists:playgrounds,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:30',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:120',
            'postcode' => 'required|string|max:20',
            'ico' => 'nullable|string|max:20',
            'start_time' => 'required|date',
            'end_time' => 'required|date',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $service = ReservationService::instance();

        try {
            $reservation = DB::transaction(function () use ($validated, $service) {
                $playground = Playground::query()->lockForUpdate()->findOrFail($validated['playground_id']);

                $tz = config('app.facility_timezone');
                $start = Carbon::parse($validated['start_time'], $tz)->setTimezone('UTC');
                $end = Carbon::parse($validated['end_time'], $tz)->setTimezone('UTC');

                if ($start->gte($end)) {
                    throw new InvalidArgumentException('Čas ukončenia musí byť po čase začiatku.');
                }

                if ($start->minute % ReservationService::SLOT_MINUTES !== 0 || $start->second !== 0) {
                    throw new InvalidArgumentException('Termín musí začínať na celej alebo polhodine.');
                }

                $durationMinutes = $start->diffInMinutes($end);

                if ($durationMinutes % ReservationService::SLOT_MINUTES !== 0) {
                    throw new InvalidArgumentException('Dĺžka termínu musí byť násobkom 30 minút.');
                }

                if ($service->hasOverlap($playground, $start, $end)) {
                    throw new InvalidArgumentException('Vybraný termín je už obsadený, zvoľte iný.');
                }

                $address = trim($validated['street'] . ', ' . $validated['postcode'] . ' ' . $validated['city']);

                // forceCreate: this is a fully explicit, server-built array (no
                // request->all()), and status/user_id/etc. are intentionally set
                // here - see the trimmed $fillable on the Reservation model.
                return Reservation::forceCreate([
                    'playground_id' => $playground->id,
                    'user_id' => null,
                    'customer_name' => $validated['customer_name'],
                    'customer_email' => $validated['customer_email'],
                    'customer_phone' => $validated['customer_phone'],
                    'ico' => $validated['ico'] ?? null,
                    'address' => $address,
                    'start_time' => $start,
                    'end_time' => $end,
                    'variable_symbol' => $service->generateVariableSymbol(),
                    'total_price' => $service->priceFor($playground, $durationMinutes),
                    'payment_method' => null,
                    'status' => Reservation::STATUS_APPROVED,
                    'verified_at' => Carbon::now(),
                    'admin_note' => $validated['admin_note'] ?? null,
                ]);
            });
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse(['message' => $e->getMessage()], 422);
        }

        return $this->successResponse($reservation->load('playground.area'), 201);
    }

    public function approve(Reservation $reservation): JsonResponse
    {
        if ($reservation->status !== Reservation::STATUS_PENDING_APPROVAL) {
            return $this->errorResponse(['message' => 'Schváliť je možné len rezervácie čakajúce na platbu.'], 422);
        }

        $reservation->update(['status' => Reservation::STATUS_APPROVED]);

        Mail::to($reservation->customer_email)->send(new ReservationApprovedMail($reservation));

        return $this->successResponse($reservation);
    }

    public function reject(Request $request, Reservation $reservation): JsonResponse
    {
        if (!in_array($reservation->status, [Reservation::STATUS_PENDING_APPROVAL, Reservation::STATUS_APPROVED], true)) {
            return $this->errorResponse(['message' => 'Túto rezerváciu nie je možné zamietnuť.'], 422);
        }

        $validated = $request->validate([
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $reservation->update([
            'status' => Reservation::STATUS_REJECTED,
            'admin_note' => $validated['admin_note'] ?? null,
        ]);

        Mail::to($reservation->customer_email)->send(new ReservationRejectedMail($reservation));

        return $this->successResponse($reservation);
    }

    public function cancel(Request $request, Reservation $reservation): JsonResponse
    {
        $validated = $request->validate([
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $reservation->update([
            'status' => Reservation::STATUS_CANCELLED,
            'admin_note' => $validated['admin_note'] ?? $reservation->admin_note,
        ]);

        return $this->successResponse($reservation);
    }

    public function resendPaymentEmail(Reservation $reservation): JsonResponse
    {
        if ($reservation->status !== Reservation::STATUS_PENDING_APPROVAL) {
            return $this->errorResponse(['message' => 'Platobný e-mail je možné znova odoslať len pre rezervácie čakajúce na platbu.'], 422);
        }

        Mail::to($reservation->customer_email)->send(new ReservationPaymentMail($reservation));

        return $this->successResponse(['message' => 'Platobný e-mail bol znova odoslaný.']);
    }

    /**
     * Downloadable payment summary PDF - only for reservations that are
     * actually paid (approved), regardless of payment method.
     */
    public function downloadPaymentSummary(Reservation $reservation): JsonResponse|Response
    {
        if ($reservation->status !== Reservation::STATUS_APPROVED) {
            return $this->errorResponse(['message' => 'Súhrn platby je dostupný len pre schválené (zaplatené) rezervácie.'], 422);
        }

        $pdf = ReservationPdfService::instance()->generatePaymentSummary($reservation);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="suhrn-platby-' . $reservation->variable_symbol . '.pdf"',
        ]);
    }

    /**
     * CSV report of reservations/payments whose reservation date (start_time)
     * falls within the given range - for admin bookkeeping/reconciliation.
     */
    public function export(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'status' => 'nullable|string',
        ]);

        $tz = config('app.facility_timezone');
        $from = Carbon::parse($validated['from'], $tz)->startOfDay()->utc();
        $to = Carbon::parse($validated['to'], $tz)->endOfDay()->utc();

        $reservations = Reservation::query()
            ->with(['playground.area'])
            ->whereBetween('start_time', [$from, $to])
            ->when($validated['status'] ?? null, fn($query, $status) => $query->where('status', $status))
            ->orderBy('start_time')
            ->get();

        $statusLabels = [
            Reservation::STATUS_UNVERIFIED => 'Čaká na overenie e-mailu',
            Reservation::STATUS_AWAITING_PAYMENT => 'Čaká na platbu kartou',
            Reservation::STATUS_PENDING_APPROVAL => 'Čaká na platbu',
            Reservation::STATUS_APPROVED => 'Schválené',
            Reservation::STATUS_REJECTED => 'Zamietnuté',
            Reservation::STATUS_CANCELLED => 'Zrušené',
        ];

        $paymentMethodLabels = [
            Reservation::PAYMENT_METHOD_CARD => 'Karta',
            Reservation::PAYMENT_METHOD_BANK_TRANSFER => 'Prevod',
        ];

        $filename = 'report-platieb-' . $validated['from'] . '_' . $validated['to'] . '.csv';

        return new StreamedResponse(function () use ($reservations, $statusLabels, $paymentMethodLabels) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM so Excel renders Slovak diacritics correctly.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Dátum a čas',
                'Meno',
                'E-mail',
                'Ihrisko',
                'Areál',
                'Variabilný symbol',
                'Suma (EUR)',
                'Spôsob platby',
                'Stav',
            ], ';');

            foreach ($reservations as $reservation) {
                fputcsv($handle, [
                    $reservation->startTimeLocal()->format('d.m.Y H:i') . '–' . $reservation->endTimeLocal()->format('H:i'),
                    $this->csvSafe($reservation->customer_name),
                    $this->csvSafe($reservation->customer_email),
                    $this->csvSafe($reservation->playground?->name),
                    $this->csvSafe($reservation->playground?->area?->name),
                    $this->csvSafe($reservation->variable_symbol),
                    number_format((float)$reservation->total_price, 2, ',', ''),
                    $paymentMethodLabels[$reservation->payment_method] ?? $reservation->payment_method,
                    $statusLabels[$reservation->status] ?? $reservation->status,
                ], ';');
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Neutralises spreadsheet formula injection: a cell whose first character
     * is one a spreadsheet treats as a formula (= + - @, or a leading tab/CR)
     * gets an apostrophe prefix so Excel/Sheets renders it as literal text
     * instead of executing it. Customer name/email are attacker-controlled
     * (guest booking), so they must never be trusted in an exported CSV.
     */
    private function csvSafe(?string $value): string
    {
        $value = (string) $value;

        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }
}
