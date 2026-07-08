@extends('layouts.admin')

@section('title', 'تنظیمات')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('put')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">نام آموزشگاه</label>
                        <input name="institute_name" value="{{ old('institute_name', $settings->get('institute_name', 'آموزشگاه')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">شماره تماس آموزشگاه</label>
                        <input name="contact_phone" value="{{ old('contact_phone', $settings->get('contact_phone')) }}" class="form-control ltr">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">مهلت پیشفرض پرداخت به ساعت</label>
                        <input type="number" name="default_payment_deadline_hours" value="{{ old('default_payment_deadline_hours', $settings->get('default_payment_deadline_hours', 24)) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">اعتبار پیشفرض لینک به ساعت</label>
                        <input type="number" name="default_public_link_expiration_hours" value="{{ old('default_public_link_expiration_hours', $settings->get('default_public_link_expiration_hours', 48)) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">مدت هر رزرو به دقیقه</label>
                        <input type="number" name="reservation_duration_minutes" value="{{ old('reservation_duration_minutes', $settings->reservationDurationMinutes()) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">مبلغ پیش پرداخت پیشفرض</label>
                        <input type="number" name="default_prepayment_amount" value="{{ old('default_prepayment_amount', $settings->get('default_prepayment_amount', 0)) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">حداکثر حجم فیش به کیلوبایت</label>
                        <input type="number" name="max_receipt_image_size_kb" value="{{ old('max_receipt_image_size_kb', $settings->get('max_receipt_image_size_kb', 5120)) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">فرمتهای مجاز فیش</label>
                        <input name="allowed_receipt_formats" value="{{ old('allowed_receipt_formats', implode(',', $settings->receiptFormats())) }}" class="form-control ltr">
                    </div>
                    <div class="col-12">
                        <label class="form-label">قالب پیام لینک عمومی</label>
                        <textarea name="public_link_message_template" rows="2" class="form-control">{{ old('public_link_message_template', $settings->get('public_link_message_template')) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">نوع کنکورها</label>
                        <textarea name="exam_types" rows="5" class="form-control">{{ old('exam_types', implode("\n", $settings->get('exam_types', []))) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">رشتهها</label>
                        <textarea name="majors" rows="5" class="form-control">{{ old('majors', implode("\n", $settings->get('majors', []))) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">متن رزرو منقضی شده</label>
                        <textarea name="expired_message" rows="3" class="form-control">{{ old('expired_message', $settings->get('expired_message')) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">متن رزرو لغو شده</label>
                        <textarea name="cancelled_message" rows="3" class="form-control">{{ old('cancelled_message', $settings->get('cancelled_message')) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-check">
                            <input type="checkbox" name="release_slot_after_payment_rejection" value="1" class="form-check-input"
                                @checked(old('release_slot_after_payment_rejection', $settings->get('release_slot_after_payment_rejection', true)))>
                            <span class="form-check-label">بعد از رد فیش، تایم آزاد شود</span>
                        </label>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="btn btn-primary">ذخیره تنظیمات</button>
                </div>
            </form>
        </div>
    </div>
@endsection
