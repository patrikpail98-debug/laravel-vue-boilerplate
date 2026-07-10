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
use Illuminate\Support\Facades\Mail;

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
}
