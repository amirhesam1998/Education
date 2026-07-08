# سامانه رزرو مشاوره کنکور

این پروژه یک سامانه Laravel برای مدیریت رزرو وقت مشاوره انتخاب رشته در آموزشگاه است. رزروها توسط ادمین، مدیر شعبه یا اپراتور ساخته می‌شوند و دانش آموز حساب کاربری ندارد؛ فقط از طریق لینک امن عمومی اطلاعات ناقص را تکمیل می‌کند یا فیش پیش پرداخت را بارگذاری می‌کند.

## نصب

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

اگر از فایلهای آپلودی خصوصی استفاده می‌کنید، نیازی به `storage:link` برای فیشها نیست؛ فیشها روی دیسک `local` و داخل `storage/app/private` ذخیره می‌شوند و فقط از مسیر محافظت شده ادمین نمایش داده می‌شوند.

## متغیرهای مهم محیطی

```env
APP_NAME="سامانه رزرو مشاوره"
APP_TIMEZONE=Asia/Tehran
APP_LOCALE=fa
APP_FALLBACK_LOCALE=en
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

پیشفرض پروژه SQLite است. اگر PHP شما `pdo_sqlite` ندارد، مقادیر `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, و `DB_PASSWORD` را برای MySQL تنظیم کنید و سپس migrate را اجرا کنید.

## ورود پیشفرض

پس از اجرای seeder:

- آدرس: `/login`
- ایمیل: `admin@example.com`
- رمز عبور: `password`
- نقش: `Super Admin`

## وضعیتهای رزرو

- `draft`: رزرو اولیه
- `pending_completion`: در انتظار تکمیل اطلاعات
- `pending_prepayment`: در انتظار پرداخت
- `pending_payment_approval`: در انتظار تأیید فیش
- `confirmed`: تأیید شده
- `payment_rejected`: فیش رد شده
- `expired`: منقضی شده
- `cancelled`: لغو شده
- `completed`: انجام شده
- `no_show`: عدم حضور

## جریان پرداخت

اگر ادمین پیش پرداخت را فعال کند، مبلغ و مهلت پرداخت الزامی است. لینک عمومی تا همان مهلت معتبر می‌ماند. دانش آموز پس از تکمیل اطلاعات، فیش را آپلود می‌کند و رزرو به حالت `pending_payment_approval` می‌رود. حسابدار فیش را تأیید یا رد می‌کند. تأیید فیش رزرو را `confirmed` می‌کند.

اگر پیش پرداخت لازم نباشد، رزرو پس از کامل شدن اطلاعات دانش آموز مستقیماً `confirmed` می‌شود.

## قفل بودن تایم

تایم زمانی آزاد است که تعداد رزروهای فعال آن کمتر از ظرفیت باشد. وضعیتهای فعال:

- `pending_completion`
- `pending_prepayment`
- `pending_payment_approval`
- `confirmed`

رد فیش پرداخت به صورت پیشفرض تایم را آزاد می‌کند. این رفتار در تنظیمات با گزینه «بعد از رد فیش، تایم آزاد شود» قابل تغییر است.

## زمانبندی انقضا

کامند زیر رزروهای در انتظار را که مهلت پرداخت یا اعتبار لینک آنها گذشته باشد منقضی می‌کند:

```bash
php artisan reservations:expire
```

در scheduler ثبت شده و هر پنج دقیقه اجرا می‌شود. روی سرور production کران Laravel را فعال کنید:

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## نقشها و دسترسیها

پکیج `spatie/laravel-permission` استفاده شده است. نقشهای اولیه:

- `Super Admin`
- `Admin / Branch Manager`
- `Operator`
- `Accountant`
- `Consultant`

دسترسیها در `database/seeders/PermissionRoleSeeder.php` تعریف شده‌اند.
