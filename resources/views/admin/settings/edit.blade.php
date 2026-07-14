@extends('layouts.admin')

@section('title', 'تنظیمات')


@push('styles')
<style>
    /* ===========================================================
       Settings page — page-local styles
    =========================================================== */
    .form-card{ margin-bottom: 1rem; }
    .form-card .card-header{
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: .95rem;
    }
    .form-card .card-header i{ color: var(--brand-600); font-size: 1.05rem; }
    .form-card .card-body{ padding: 1.25rem; }

    .form-hint{
        font-size: .76rem;
        color: var(--ink-500);
        margin-top: .3rem;
    }

    .unit-suffix{ position: relative; }
    
    .unit-suffix span{
        position: absolute;
        inset-inline-end: .7rem;
        top: 50%;
        transform: translateY(-50%);
        font-size: .76rem;
        color: var(--ink-500);
        pointer-events: none;
    }

    /* Toggle switch */
    .switch-option{
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: .9rem 1rem;
        background: var(--bg);
    }
    .switch-option-text{ font-size: .87rem; font-weight: 600; color: var(--ink-900); }
    .switch-option-hint{ font-size: .78rem; color: var(--ink-500); margin-top: .15rem; }

    .form-switch .form-check-input{
        width: 2.6rem;
        height: 1.4rem;
        border-color: var(--ink-300);
        flex-shrink: 0;
        cursor: pointer;
    }
    .form-switch .form-check-input:checked{
        background-color: var(--brand-500);
        border-color: var(--brand-500);
    }
    .form-switch .form-check-input:focus{
        box-shadow: 0 0 0 .2rem rgba(47,143,131,.15);
        border-color: var(--brand-500);
    }

    .form-actions{
        position: sticky;
        bottom: 0;
        background: var(--bg);
        padding: .9rem 0 .2rem;
    }
    .form-actions .btn{ min-width: 160px; }
    .time-input-ms{padding:.55rem 0.8rem .55rem 3.8rem;}
    .settings-repeat-row{
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: .9rem;
        background: var(--bg);
        margin-bottom: .75rem;
    }

    @media (max-width: 575.98px){
        .form-card .card-body{ padding: 1rem; }
        .form-actions .btn{ width: 100%; }
    }
</style>
@endpush

@section('content')
    <form method="post" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('put')
        @php
            $presetRows = old('prepayment_presets', $settings->prepaymentAmountPresets());
            $presetRows = array_pad($presetRows, count($presetRows) + 3, ['amount' => '', 'label' => '', 'is_active' => true]);
            $cardRows = old('payment_cards', $paymentCards->map(fn ($card) => [
                'id' => $card->id,
                'holder_name' => $card->holder_name,
                'card_number' => $card->card_number,
                'bank_name' => $card->bank_name,
                'description' => $card->description,
                'is_active' => $card->is_active,
            ])->all());
            $cardRows = array_pad($cardRows, count($cardRows) + 2, ['id' => '', 'holder_name' => '', 'card_number' => '', 'bank_name' => '', 'description' => '', 'is_active' => true]);
        @endphp

        {{-- Institute info --}}
        <div class="card form-card">
            <div class="card-header"><i class="ri-building-line"></i> اطلاعات آموزشگاه</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">نام آموزشگاه</label>
                        <input name="institute_name" value="{{ old('institute_name', $settings->get('institute_name', 'آموزشگاه')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">شماره تماس آموزشگاه</label>
                        <input name="contact_phone" value="{{ old('contact_phone', $settings->get('contact_phone')) }}" class="form-control ltr">
                    </div>
                </div>
            </div>
        </div>

        {{-- Booking & payment defaults --}}
        <div class="card form-card">
            <div class="card-header"><i class="ri-time-line"></i> تنظیمات رزرو و پرداخت</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">مهلت پیشفرض پرداخت</label>
                        <div class="unit-suffix">
                            <input type="number" name="default_payment_deadline_hours" value="{{ old('default_payment_deadline_hours', $settings->get('default_payment_deadline_hours', 24)) }}" class="form-control time-input-ms">
                            <span>ساعت</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">اعتبار پیشفرض لینک</label>
                        <div class="unit-suffix">
                            <input type="number" name="default_public_link_expiration_hours" value="{{ old('default_public_link_expiration_hours', $settings->get('default_public_link_expiration_hours', 48)) }}" class="form-control time-input-ms">
                            <span>ساعت</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">مدت هر رزرو</label>
                        <div class="unit-suffix">
                            <input type="number" name="reservation_duration_minutes" value="{{ old('reservation_duration_minutes', $settings->reservationDurationMinutes()) }}" class="form-control time-input-ms">
                            <span>دقیقه</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">مبلغ پیش پرداخت پیشفرض</label>
                        <div class="unit-suffix">
                            <input type="number" name="default_prepayment_amount" value="{{ old('default_prepayment_amount', $settings->get('default_prepayment_amount', 0)) }}" class="form-control time-input-ms">
                            <span>تومان</span>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="switch-option form-switch">
                            <div>
                                <div class="switch-option-text">بعد از رد فیش، تایم آزاد شود</div>
                                <div class="switch-option-hint">در صورت فعال بودن، پس از رد فیش پرداخت دانش‌آموز، تایم مجدداً برای رزرو در دسترس قرار می‌گیرد.</div>
                            </div>
                            <input type="checkbox" name="release_slot_after_payment_rejection" value="1" class="form-check-input" role="switch"
                                @checked(old('release_slot_after_payment_rejection', $settings->get('release_slot_after_payment_rejection', true)))>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card form-card">
            <div class="card-header"><i class="ri-money-dollar-circle-line"></i> مبالغ پیش‌فرض پیش‌پرداخت</div>
            <div class="card-body">
                @foreach($presetRows as $index => $preset)
                    <div class="settings-repeat-row">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">مبلغ پیش‌پرداخت</label>
                                <input type="number" name="prepayment_presets[{{ $index }}][amount]" value="{{ $preset['amount'] ?? '' }}" class="form-control">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">برچسب</label>
                                <input name="prepayment_presets[{{ $index }}][label]" value="{{ $preset['label'] ?? '' }}" class="form-control" placeholder="مثلاً ۵۰۰,۰۰۰ تومان">
                            </div>
                            <div class="col-md-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="prepayment_presets[{{ $index }}][is_active]" value="1" class="form-check-input" @checked($preset['is_active'] ?? true)>
                                    <label class="form-check-label">فعال</label>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
                <div class="form-hint">ردیف‌های خالی ذخیره نمی‌شوند.</div>
            </div>
        </div>

        <div class="card form-card">
            <div class="card-header"><i class="ri-bank-card-line"></i> کارت‌های پرداخت</div>
            <div class="card-body">
                @foreach($cardRows as $index => $card)
                    <div class="settings-repeat-row">
                        <input type="hidden" name="payment_cards[{{ $index }}][id]" value="{{ $card['id'] ?? '' }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">صاحب کارت</label>
                                <input name="payment_cards[{{ $index }}][holder_name]" value="{{ $card['holder_name'] ?? '' }}" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">شماره کارت</label>
                                <input name="payment_cards[{{ $index }}][card_number]" value="{{ $card['card_number'] ?? '' }}" class="form-control ltr">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">بانک</label>
                                <input name="payment_cards[{{ $index }}][bank_name]" value="{{ $card['bank_name'] ?? '' }}" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">توضیحات</label>
                                <input name="payment_cards[{{ $index }}][description]" value="{{ $card['description'] ?? '' }}" class="form-control">
                            </div>
                            <div class="col-md-1">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="payment_cards[{{ $index }}][is_active]" value="1" class="form-check-input" @checked($card['is_active'] ?? true)>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
                <div class="form-hint">برای حذف عملی، کارت را غیرفعال کنید تا در رزروهای جدید نمایش داده نشود.</div>
            </div>
        </div>

        {{-- Receipt rules --}}
        <div class="card form-card">
            <div class="card-header"><i class="ri-file-shield-2-line"></i> قوانین فیش پرداخت</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">حداکثر حجم فیش</label>
                        <div class="unit-suffix">
                            <input type="number" name="max_receipt_image_size_kb" value="{{ old('max_receipt_image_size_kb', $settings->get('max_receipt_image_size_kb', 5120)) }}" class="form-control time-input-ms">
                            <span>کیلوبایت</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">حداکثر حجم کارنامه</label>
                        <div class="unit-suffix">
                            <input type="number" name="report_card_max_upload_size_mb" value="{{ old('report_card_max_upload_size_mb', $settings->get('report_card_max_upload_size_mb', 10)) }}" class="form-control time-input-ms">
                            <span>مگابایت</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">فرمتهای مجاز فیش</label>
                        <input name="allowed_receipt_formats" value="{{ old('allowed_receipt_formats', implode(',', $settings->receiptFormats())) }}" class="form-control ltr" placeholder="jpg,png,pdf">
                        <div class="form-hint">فرمتها را با کاما (,) از هم جدا کنید.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Message templates --}}
        <div class="card form-card">
            <div class="card-header"><i class="ri-chat-3-line"></i> قالبهای پیام</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">قالب پیام لینک عمومی</label>
                        <textarea name="public_link_message_template" rows="2" class="form-control">{{ old('public_link_message_template', $settings->get('public_link_message_template')) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">متن رزرو منقضی شده</label>
                        <textarea name="expired_message" rows="3" class="form-control">{{ old('expired_message', $settings->get('expired_message')) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">متن رزرو لغو شده</label>
                        <textarea name="cancelled_message" rows="3" class="form-control">{{ old('cancelled_message', $settings->get('cancelled_message')) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reference lists --}}
        <div class="card form-card">
            <div class="card-header"><i class="ri-list-check-2"></i> فهرستهای مرجع</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">نوع کنکورها</label>
                        <textarea name="exam_types" rows="5" class="form-control">{{ old('exam_types', implode("\n", $settings->get('exam_types', []))) }}</textarea>
                        <div class="form-hint">هر مورد را در یک خط جداگانه بنویسید.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">رشتهها</label>
                        <textarea name="majors" rows="5" class="form-control">{{ old('majors', implode("\n", $settings->get('majors', []))) }}</textarea>
                        <div class="form-hint">هر مورد را در یک خط جداگانه بنویسید.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button class="btn btn-primary">
                <i class="ri-save-line align-middle"></i> ذخیره تنظیمات
            </button>
        </div>
    </form>
@endsection
