<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BoardingHouse;
use App\Models\BoardingHousePhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class OwnerListingPhotoController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $boardingHouse = $this->getOwnerBoardingHouse($request);

        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $uploadedFile = $validated['photo'];

        $filePath = $uploadedFile->store(
            'boarding-houses/' . $boardingHouse->id . '/photos',
            'public'
        );

        try {
            DB::transaction(function () use ($request, $boardingHouse, $uploadedFile, $filePath, $validated) {
                $hasPrimaryPhoto = $boardingHouse->photos()
                    ->where('is_primary', true)
                    ->exists();

                $nextSortOrder = (int) $boardingHouse->photos()->max('sort_order') + 1;

                $photo = BoardingHousePhoto::create([
                    'boarding_house_id' => $boardingHouse->id,
                    'file_path' => $filePath,
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'mime_type' => $uploadedFile->getClientMimeType(),
                    'file_size' => $uploadedFile->getSize(),
                    'alt_text' => $validated['alt_text'] ?? $boardingHouse->name,
                    'is_primary' => ! $hasPrimaryPhoto,
                    'sort_order' => $nextSortOrder,
                ]);

                ActivityLog::create([
                    'user_id' => $request->user()->id,
                    'boarding_house_id' => $boardingHouse->id,
                    'action' => 'owner_photo_uploaded',
                    'description' => 'Owner uploaded a photo for ' . $boardingHouse->name . '.',
                    'properties' => [
                        'photo_id' => $photo->id,
                        'file_path' => $photo->file_path,
                    ],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($filePath);

            throw $exception;
        }

        return back()->with('success', 'Photo uploaded successfully.');
    }

    public function setPrimary(Request $request, BoardingHousePhoto $photo): RedirectResponse
    {
        $boardingHouse = $this->getOwnerBoardingHouse($request);

        abort_unless($photo->boarding_house_id === $boardingHouse->id, 403);

        DB::transaction(function () use ($request, $boardingHouse, $photo) {
            BoardingHousePhoto::query()
                ->where('boarding_house_id', $boardingHouse->id)
                ->update([
                    'is_primary' => false,
                ]);

            $photo->update([
                'is_primary' => true,
            ]);

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'boarding_house_id' => $boardingHouse->id,
                'action' => 'owner_photo_set_primary',
                'description' => 'Owner set a primary photo for ' . $boardingHouse->name . '.',
                'properties' => [
                    'photo_id' => $photo->id,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return back()->with('success', 'Primary photo updated successfully.');
    }

    public function destroy(Request $request, BoardingHousePhoto $photo): RedirectResponse
    {
        $boardingHouse = $this->getOwnerBoardingHouse($request);

        abort_unless($photo->boarding_house_id === $boardingHouse->id, 403);

        $wasPrimary = $photo->is_primary;
        $filePath = $photo->file_path;

        DB::transaction(function () use ($request, $boardingHouse, $photo, $wasPrimary) {
            $photoId = $photo->id;

            $photo->delete();

            if ($wasPrimary) {
                $nextPhoto = BoardingHousePhoto::query()
                    ->where('boarding_house_id', $boardingHouse->id)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->first();

                if ($nextPhoto) {
                    $nextPhoto->update([
                        'is_primary' => true,
                    ]);
                }
            }

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'boarding_house_id' => $boardingHouse->id,
                'action' => 'owner_photo_deleted',
                'description' => 'Owner deleted a photo for ' . $boardingHouse->name . '.',
                'properties' => [
                    'photo_id' => $photoId,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        Storage::disk('public')->delete($filePath);

        return back()->with('success', 'Photo deleted successfully.');
    }

    private function getOwnerBoardingHouse(Request $request): BoardingHouse
    {
        return BoardingHouse::query()
            ->where('owner_id', $request->user()->id)
            ->firstOrFail();
    }
}