<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Quotation;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Booking::query()->with(['quotation.inquiry.customer']);

        $query->when(request('q'), function ($q) {
            $term = request('q');
            $q->where('booking_number', 'like', "%{$term}%")
                ->orWhereHas('quotation', function ($quo) use ($term) {
                    $quo->where('quotation_number', 'like', "%{$term}%")
                        ->orWhereHas('inquiry.customer', function ($c) use ($term) {
                            $c->where('name', 'like', "%{$term}%");
                        });
                });
        });

        $query->when(request('status'), fn ($q) => $q->where('status', request('status')));
        $query->when(request('quotation_id'), fn ($q) => $q->where('quotation_id', request('quotation_id')));
        $query->when(request('travel_from'), fn ($q) => $q->whereDate('travel_date', '>=', request('travel_from')));
        $query->when(request('travel_to'), fn ($q) => $q->whereDate('travel_date', '<=', request('travel_to')));

        $perPage = (int) request('per_page', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

        $bookings = $query->latest()->paginate($perPage)->withQueryString();
        $quotations = Quotation::query()->with('inquiry.customer')->orderBy('created_at', 'desc')->get();

        return view('operations.bookings.index', compact('bookings', 'quotations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $quotations = Quotation::query()
            ->with('inquiry.customer')
            ->whereDoesntHave('booking')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('operations.bookings.create', compact('quotations'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookingRequest $request)
    {
        $validated = $request->validated();
        $validated['booking_number'] = $this->generateBookingNumber();

        Booking::query()->create($validated);

        return redirect()
            ->route('operations.bookings.index')
            ->with('success', 'Booking created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Booking $booking)
    {
        $booking->load(['quotation.inquiry.customer']);

        return view('operations.bookings.show', compact('booking'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Booking $booking)
    {
        $quotations = Quotation::query()
            ->with('inquiry.customer')
            ->where(function ($q) use ($booking) {
                $q->whereDoesntHave('booking')
                    ->orWhere('id', $booking->quotation_id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('operations.bookings.edit', compact('booking', 'quotations'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookingRequest $request, Booking $booking)
    {
        $validated = $request->validated();
        $booking->update($validated);

        return redirect()
            ->route('operations.bookings.index')
            ->with('success', 'Booking updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Booking $booking)
    {
        $booking->delete();

        return redirect()
            ->route('operations.bookings.index')
            ->with('success', 'Booking deleted successfully.');
    }

    public function exportCsv(): StreamedResponse
    {
        $query = Booking::query()->with(['quotation.inquiry.customer']);

        $query->when(request('q'), function ($q) {
            $term = request('q');
            $q->where('booking_number', 'like', "%{$term}%")
                ->orWhereHas('quotation', function ($quo) use ($term) {
                    $quo->where('quotation_number', 'like', "%{$term}%")
                        ->orWhereHas('inquiry.customer', function ($c) use ($term) {
                            $c->where('name', 'like', "%{$term}%");
                        });
                });
        });

        $query->when(request('status'), fn ($q) => $q->where('status', request('status')));
        $query->when(request('quotation_id'), fn ($q) => $q->where('quotation_id', request('quotation_id')));
        $query->when(request('travel_from'), fn ($q) => $q->whereDate('travel_date', '>=', request('travel_from')));
        $query->when(request('travel_to'), fn ($q) => $q->whereDate('travel_date', '<=', request('travel_to')));

        $bookings = $query->latest()->get();

        return response()->streamDownload(function () use ($bookings) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'booking_number',
                'quotation_number',
                'customer_name',
                'travel_date',
                'status',
                'created_at',
            ]);

            foreach ($bookings as $booking) {
                fputcsv($handle, [
                    $booking->booking_number,
                    $booking->quotation->quotation_number ?? '',
                    $booking->quotation->inquiry->customer->name ?? '',
                    optional($booking->travel_date)->format('Y-m-d'),
                    $booking->status,
                    optional($booking->created_at)->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($handle);
        }, 'bookings-export.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function generateBookingNumber(): string
    {
        do {
            $number = 'BK-' . now()->format('Ymd') . '-' . random_int(1000, 9999);
        } while (Booking::query()->where('booking_number', $number)->exists());

        return $number;
    }
}
