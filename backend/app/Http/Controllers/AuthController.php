<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate->Support\Facades\Hash;
use App\Models\User;
use Illuminate->Validation\ValidationException;
use Illuminate->Support->Facades\Auth;
use Illuminate->Support\Facades\Log;

/**
 * @group Authentication
 *
 * 管理用戶註冊、登入和登出。
 */
class AuthController extends Controller
{
    /**
     * 註冊新用戶。
     *
     * 註冊一個新用戶並返回 Sanctum API Token。
     *
     * @bodyParam name string required 用戶名。Example: John Doe
     * @bodyParam email string required 用戶的 Email 地址，必須是唯一的。Example: john@example.com
     * @bodyParam password string required 用戶密碼，至少8個字符，且需要與 password_confirmation 匹配。Example: password123
     * @bodyParam password_confirmation string required 確認用戶密碼。Example: password123
     * @response {
     * "message": "User registered successfully",
     * "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
     * "user": {"id": 1, "name": "John Doe", "email": "john@example.com"}
     * }
     * @response 422 {
     * "message": "註冊驗證失敗",
     * "errors": {"email": ["The email has already been taken."]}
     * }
     */
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);
        } catch (ValidationException $e) {
            Log::error('註冊驗證失敗: ' . json_encode($e->errors()));
            return response()->json(['message' => '註冊驗證失敗', 'errors' => $e->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info("用戶 {$user->email} 註冊成功。");
        return response()->json([
            'message' => 'User registered successfully',
            'token' => $token,
            'user' => $user
        ], 201);
    }

    /**
     * 用戶登入。
     *
     * 驗證用戶憑證並返回 Sanctum API Token。
     *
     * @bodyParam email string required 用戶的 Email 地址。Example: john@example.com
     * @bodyParam password string required 用戶密碼。Example: password123
     * @response {
     * "message": "Login successful",
     * "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
     * "user": {"id": 1, "name": "John Doe", "email": "john@example.com"}
     * }
     * @response 401 {
     * "message": "Invalid credentials"
     * }
     * @response 422 {
     * "message": "登入驗證失敗",
     * "errors": {"email": ["The email field is required."]}
     * }
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            Log::error('登入驗證失敗: ' . json_encode($e->errors()));
            return response()->json(['message' => '登入驗證失敗', 'errors' => $e->errors()], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            Log::warning("登入嘗試失敗：無效的憑證，Email: {$request->email}");
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = $request->user();
        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info("用戶 {$user->email} 登入成功。");
        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user
        ]);
    }

    /**
     * 用戶登出。
     *
     * 撤銷當前認證用戶的所有 Sanctum Token。
     * @authenticated
     * @response {
     * "message": "Successfully logged out"
     * }
     * @response 401 {
     * "message": "Unauthenticated."
     * }
     */
    public function logout(Request $request)
    {
        // 撤銷當前 token
        $request->user()->currentAccessToken()->delete();
        Log::info("用戶 {$request->user()->email} 登出成功。");
        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * 獲取認證用戶信息。
     *
     * 返回當前認證用戶的詳細信息。
     * @authenticated
     * @response {
     * "id": 1,
     * "name": "John Doe",
     * "email": "john@example.com",
     * "email_verified_at": "2023-01-01T00:00:00.000000Z",
     * "created_at": "2023-01-01T00:00:00.000000Z",
     * "updated_at": "2023-01-01T00:00:00.000000Z"
     * }
     * @response 401 {
     * "message": "Unauthenticated."
     * }
     */
    public function user(Request $request)
    {
        return $request->user();
    }
}
