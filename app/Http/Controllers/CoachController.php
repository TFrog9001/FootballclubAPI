<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

use App\Services\UploadService;
use App\Services\ValidationService;
use App\Helpers\UserHelper;
use App\Models\User;
use App\Models\Coach;
use App\Http\Resources\CoachResource;

class CoachController extends Controller
{
    private $uploadService;
    private $validationService;

    public function __construct(UploadService $uploadService, ValidationService $validationService)
    {
        $this->uploadService = $uploadService;
        $this->validationService = $validationService;
    }

    // Get coach

    public function index()
    {
        try {
            $coaches = Coach::with('user', 'contract')
                ->get();

            $coacheResources = CoachResource::collection($coaches);

            return response()->json(['coaches' => $coacheResources], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function show($slug)
    {
        try {
            // Chuyển đổi slug thành tên
            $name = Str::title(str_replace('-', ' ', $slug));

            $coach = Coach::join('users', 'coaches.user_id', '=', 'users.user_id')
                ->select('coaches.*', 'users.*')
                ->where('users.name', $name)
                ->firstOrFail();

            $coachResource = new CoachResource($coach);

            return response()->json(['coach' => $coachResource], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function showId($id)
    {
        try {
            $coach = Coach::join('users', 'coaches.user_id', '=', 'users.user_id')
                ->select('coaches.*', 'users.*')
                ->where('users.user_id', $id)
                ->firstOrFail();

            $coachResource = new CoachResource($coach);

            return response()->json(['coach' => $coachResource], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            // return $request;
            // Lấy các quy tắc xác thực từ ValidationService
            $validatedData = $this->validationService->getUserValidationRules($request)
                + $this->validationService->getCoachValidationRules($request);

            // Thực hiện xác thực dữ liệu
            $this->validate($request, $validatedData);

            // Tạo user ID độc nhất cho HLV
            $userID = UserHelper::generateUserID('C');

            // Upload ảnh với sự trợ giúp của UploadService
            $imagesPath = $this->uploadService->uploadImage($request, 'user', $userID);

            $user = User::create([
                'user_id' => $userID ?? 'C0000000',
                'name' => $request['name'],
                'email' => $request['email'],
                'password' => Hash::make($request['password']),
                'date_of_birth' => $request['date_of_birth'],
                'flag' => $request['flag'],
                'nationality' => $request['nationality'],
                'role_id' => 3,
                'image' => $imagesPath,
            ]);

            $coach = Coach::create([
                'user_id' => $userID,
                'position' => $request['position'],
                'wins' => $request['wins'] ?? 0,
                'losses' => $request['losses'] ?? 0,
                'draws' => $request['draws'] ?? 0,
            ]);

            return response()->json(['message' => 'Coach registered successfully', 'user' => $user]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 400);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $user_id)
    {
        try {
            // Kiểm tra dữ liệu vào
            $this->validate($request, [
                'name' => 'string|max:255',
                'email' => [
                    'email',
                    Rule::unique('users', 'email')->ignore($user_id, 'user_id'),
                ],
                'date_of_birth' => 'nullable|date',
                'nationality' => 'nullable|string|max:255',
                'image' => 'image|mimes:jpeg,png,jpg,webp,PNG,JPG|max:2048',
                'flag' => 'nullable|string|max:255',
                'position' => 'nullable|in:head,assistant,fitness,goalkeeping,youth,tactical,rehabilitation,consultant',
                'wins' => 'nullable|integer',
                'losses' => 'nullable|integer',
                'draws' => 'nullable|string',
                'detail' => 'nullable|string'
            ]);

            // Tìm cầu thủ cần cập nhật
            $coach = Coach::where('user_id', $user_id)->first();

            if ($coach) {
                // Lấy các quy tắc xác thực từ ValidationService


                // Tìm người dùng liên quan và cập nhật thông tin người dùng
                $user = User::where('user_id', $user_id)->first();

                if ($user) {
                    $user->update($request->only(['name', 'email', 'date_of_birth', 'nationality', 'flag']));

                    // Lấy đường dẫn ảnh cũ để xóa
                    $oldImage = $user->image;

                    // Kiểm tra và tải lên ảnh người dùng mới nếu có
                    if ($request->hasFile('image')) {
                        if ($oldImage) {

                            $oldImagePath = str_replace('/storage/', 'public/', $oldImage);

                            // Kiểm tra xem ảnh cũ có tồn tại hay không
                            if (Storage::exists($oldImagePath)) {
                                Storage::delete($oldImagePath);
                            }
                        }

                        // Tải lên ảnh mới
                        $userImageURL = $this->uploadService->uploadImage($request, 'user', $user->user_id);
                        $user->image = $userImageURL;
                    }

                    // Lưu người dùng cập nhật vào cơ sở dữ liệu
                    $user->save();

                    // Cập nhật thông tin cầu thủ
                    $coach->fill($request->only(['position','wins', 'losses', 'draws', 'detail']));

                    // Lưu cầu thủ cập nhật vào cơ sở dữ liệu
                    $coach->save();
                }

                // Trả về thông báo thành công và thông tin cầu thủ cập nhật
                return response()->json(['message' => 'Coach updated successfully', 'coach' => new CoachResource($coach)], 200);
            } else {
                // Trả về thông báo nếu cầu thủ không tồn tại
                return response()->json(['message' => 'Coach not found'], 404);
            }
        } catch (ValidationException $e) {
            // Trả về thông báo lỗi xác thực nếu có lỗi
            return response()->json(['errors' => $e->errors()], 400);
        } catch (Exception $e) {
            // Trả về thông báo lỗi nếu có lỗi khác
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete($user_id)
    {
        try {
            // Tìm cầu thủ cần xóa
            $coach = Coach::where('user_id', $user_id)->first();

            if ($coach) {
                // Tìm và xóa người dùng liên quan
                $user = User::where('user_id', $user_id)->first();

                if ($user) {
                    $directoryToDelete = 'public/upload/clubs/' . $user->user_id;

                    // Check if the directory exists
                    if (Storage::exists($directoryToDelete)) {
                        // Delete the directory along with its contents
                        Storage::deleteDirectory($directoryToDelete);
                    }
                    // Xóa người dùng
                    $user->delete();
                }

                return response()->json(['message' => 'Coach deleted successfully'], 200);
            } else {
                // Trả về thông báo nếu cầu thủ không tồn tại
                return response()->json(['message' => 'Coach not found'], 404);
            }
        } catch (Exception $e) {
            // Trả về thông báo lỗi nếu có lỗi khác
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
