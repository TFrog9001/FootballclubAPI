<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClubResource;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Exception;

use App\Services\UploadService;
use App\Services\ValidationService;

use App\Models\Club;

class ClubController extends Controller
{
    private $uploadService;
    private $validationService;

    public function __construct(UploadService $uploadService, ValidationService $validationService)
    {
        $this->uploadService = $uploadService;
        $this->validationService = $validationService;
    }

    public function index()
    {
        try {
            $clubs = Club::all();
            return response()->json(['clubs' => ClubResource::collection($clubs)], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $club = Club::findOrFail($id);
            return response()->json(['club' => new ClubResource($club)], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            // Validate the incoming request data
            $validatedData = $this->validationService->getClubValidationRules($request);
            // Thực hiện xác thực dữ liệu
            $this->validate($request, $validatedData);

            $club = new Club([
                'name' => $request['name']
            ]);

            $club->save();

            // Upload the image and get the file path
            $imagePath = $this->uploadService->uploadImage($request, 'club', $club->club_id);

            $club->image = $imagePath;

            // Save the club to the database
            $club->save();

            return response()->json(['message' => 'Club created successfully', 'club' => new ClubResource($club)], 201);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 400);
        } catch (Exception $e) {
            return response()->json(['errors' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // Find the club by ID
            $club = Club::findOrFail($id);

            // Validate the incoming request data
            $this->validate($request, [
                'name' => [
                    'string',
                    'max:100',
                    Rule::unique('clubs', 'name')->ignore($id, 'club_id'),
                ],
            ]);

            // Update the club fields
            $club->name = $request['name'];

            // If a new image is provided, upload and update the image path
            if ($request->hasFile('image')) {
                // Kiểm tra và tải lên ảnh người dùng mới nếu có
                $this->validate($request, [
                    'image' => 'image|mimes:jpeg,png,jpg,webp,PNG,JPG|max:2048',
                ]);
                $oldImage = $club->image;
                if ($oldImage) {
                    $oldImagePath = str_replace('/storage/', 'public/', $oldImage);
                    // Kiểm tra xem ảnh cũ có tồn tại hay không
                    if (Storage::exists($oldImagePath)) {
                        Storage::delete($oldImagePath);
                    }
                }
                // Tải lên ảnh mới
                $imagePath = $this->uploadService->uploadImage($request, 'club', $club->club_id);
                $club->image = $imagePath;
            }

            // Save the updated club to the database
            $club->save();

            return response()->json(['club' => new ClubResource($club)], 200);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 400);
        } catch (Exception $e) {
            return response()->json(['errors' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        try {
            // Find the club by ID
            $club = Club::find($id);

            if (!$club) {
                return response()->json(['message' => 'Club not found'], 404);
            }

            $directoryToDelete = 'public/upload/clubs/' . $club->club_id;

            // Check if the directory exists
            if (Storage::exists($directoryToDelete)) {
                // Delete the directory along with its contents
                Storage::deleteDirectory($directoryToDelete);
            }
            // Delete the club
            $club->delete();

            return response()->json(['message' => 'Club deleted successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
