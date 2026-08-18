<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Services\BookingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly BookingService $bookings) {}

    public function index(Request $request): JsonResponse
    {
        return $this->success(
            BookingResource::collection($this->bookings->listFor($request->user()))->resolve(),
            'Bookings'
        );
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $booking = $this->bookings->cancel($request->user(), $id);

        return $this->success(
            (new BookingResource($booking))->resolve(),
            'İş ləğv edildi'
        );
    }
}
