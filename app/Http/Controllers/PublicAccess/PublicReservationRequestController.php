<?php

namespace App\Http\Controllers\PublicAccess;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicAccess\StorePublicReservationRequest;
use App\Services\ReservationRequestService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PublicReservationRequestController extends Controller
{
    public function create(SettingsService $settings): View
    {
        $captcha = $this->captcha();

        return view('public.field-selection-request', [
            'examTypes' => $settings->get('exam_types', ['تجربی', 'ریاضی', 'انسانی', 'هنر', 'زبان']),
            'captchaQuestion' => $captcha['question'],
        ]);
    }

    public function store(StorePublicReservationRequest $request, ReservationRequestService $requests): RedirectResponse
    {
        $requests->createFromPublic($request->validated());

        return redirect()->route('public.reservation-requests.create')
            ->with('success', 'درخواست شما با موفقیت ثبت شد. آموزشگاه پس از بررسی با شما تماس خواهد گرفت.');
    }

    /** @return array{question:string} */
    private function captcha(): array
    {
        $first = random_int(2, 9);
        $second = random_int(1, 9);

        session(['public_reservation_request_captcha' => $first + $second]);

        return ['question' => $first.' + '.$second.' = ؟'];
    }
}
