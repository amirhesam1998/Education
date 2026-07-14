<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReservationDocumentService
{
    public function uploadReportCardFromAdmin(Reservation $reservation, UploadedFile $file, User $user): ReservationDocument
    {
        return $this->uploadReportCard($reservation, $file, ReservationDocument::SOURCE_ADMIN, User::class, $user->id);
    }

    public function uploadReportCardFromStudent(Reservation $reservation, UploadedFile $file): ReservationDocument
    {
        return $this->uploadReportCard($reservation, $file, ReservationDocument::SOURCE_STUDENT);
    }

    public function getReportCards(Reservation $reservation)
    {
        return $reservation->reportCards()->latest()->get();
    }

    public function deleteDocument(ReservationDocument $document): void
    {
        DB::transaction(function () use ($document): void {
            Storage::disk('local')->delete($document->file_path);
            $document->delete();
        });
    }

    private function uploadReportCard(
        Reservation $reservation,
        UploadedFile $file,
        string $source,
        ?string $uploadedByType = null,
        ?int $uploadedById = null,
    ): ReservationDocument {
        return DB::transaction(function () use ($reservation, $file, $source, $uploadedByType, $uploadedById): ReservationDocument {
            $reservation = Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            $reservation->reportCards()->where('source', $source)->get()->each(function (ReservationDocument $document): void {
                Storage::disk('local')->delete($document->file_path);
                $document->delete();
            });

            $path = $file->store('reservation-documents/'.$reservation->id.'/report-cards', 'local');

            return ReservationDocument::query()->create([
                'reservation_id' => $reservation->id,
                'type' => ReservationDocument::TYPE_REPORT_CARD,
                'uploaded_by_type' => $uploadedByType,
                'uploaded_by_id' => $uploadedById,
                'source' => $source,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize() ?: 0,
            ]);
        });
    }
}
