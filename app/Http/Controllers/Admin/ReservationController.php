<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ReservationApprovedMail;
use App\Mail\ReservationPaymentMail;
use App\Mail\ReservationRejectedMail;
use App\Models\Reservation;
use App\Services\ReservationPdfService;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
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
            ->orderByDesc('created_at')
            ->get();

        return $this->successResponse($reservations);
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
                    $reservation->customer_name,
                    $reservation->customer_email,
                    $reservation->playground?->name,
                    $reservation->playground?->area?->name,
                    $reservation->variable_symbol,
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
}
