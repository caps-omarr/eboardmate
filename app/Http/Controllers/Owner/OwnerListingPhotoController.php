<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BoardingHouse;
use App\Models\BoardingHousePhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str; 
use Intervention\Image\ImageManager; 
use Intervention\Image\Drivers\Gd\Driver; 
use Throwable;

class OwnerListingPhotoController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $boardingHouse = $this->getOwnerBoardingHouse($request);

        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $uploadedFile = $validated['photo'];
        $fileName = Str::uuid() . '.webp';
        $filePath = 'boarding-houses/' . $boardingHouse->id . '/photos/' . $fileName;

        try {
            
            $manager = new ImageManager(new Driver());
            $image = $manager->read($uploadedFile->getRealPath());
            $image->scaleDown(width: 1200);
            $encodedImage = $image->toWebp(80);
            
            Storage::disk('public')->put($filePath, $encodedImage->toString());
            $newFileSize = Storage::disk('public')->size($filePath);

            DB::transaction(function () use ($request, $boardingHouse, $uploadedFile, $filePath, $validated, $newFileSize) {
                $hasPrimaryPhoto = $boardingHouse->photos()
                    ->where('is_primary', true)
                    ->exists();

                $nextSortOrder = (int) $boardingHouse->photos()->max('sort_order') + 1;

                $photo = BoardingHousePhoto::create([
                    'boarding_house_id' => $boardingHouse->id,
                    'file_path' => $filePath,
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'mime_type' => 'image/webp', 
                    'file_size' => $newFileSize, 
                    'alt_text' => $validated['alt_text'] ?? $boardingHouse->name,
                    'is_primary' => ! $hasPrimaryPhoto,
                    'sort_order' => $nextSortOrder,
                ]);

                ActivityLog::create([
                    'user_id' => $request->user()->id,
                    'boarding_house_id' => $boardingHouse->id,
                    'action' => ActivityLog::ACTION_PHOTO_UPLOADED,
                    'description' => 'Owner uploaded a photo for ' . $boardingHouse->name . '.',
                    'properties' => [
                        'photo_id' => $photo->id,
                        'file_path' => $photo->file_path,
                    ],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            });

          
            Cache::forget('public_map_markers');
            Cache::forget("boarding_house_public_details_{$boardingHouse->id}");

        } catch (Throwable $exception) {
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
            throw $exception;
        }

        return back()->with('success', 'Photo uploaded and optimized successfully.');
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
                'action' => ActivityLog::ACTION_PHOTO_SET_PRIMARY,
                'description' => 'Owner set a primary photo for ' . $boardingHouse->name . '.',
                'properties' => [
                    'photo_id' => $photo->id,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        Cache::forget('public_map_markers');
        Cache::forget("boarding_house_public_details_{$boardingHouse->id}");

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
                'action' => ActivityLog::ACTION_PHOTO_DELETED,
                'description' => 'Owner deleted a photo for ' . $boardingHouse->name . '.',
                'properties' => [
                    'photo_id' => $photoId,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        Storage::disk('public')->delete($filePath);

       
        Cache::forget('public_map_markers');
        Cache::forget("boarding_house_public_details_{$boardingHouse->id}");

        return back()->with('success', 'Photo deleted successfully.');
    }

    private function getOwnerBoardingHouse(Request $request): BoardingHouse
    {
        return BoardingHouse::query()
            ->where('owner_id', $request->user()->id)
            ->firstOrFail();
    }
}