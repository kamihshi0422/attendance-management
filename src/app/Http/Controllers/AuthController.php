<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth/register');
    }

    public function register(RegisterRequest $request)
    {
        $validatedData = $request->validated();// rules()で許可した項目だけ抽出され

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        // ここでメール認証メールを送信
        event(new Registered($user));

        Auth::login($user);// $userのidをセッションに保存　強制ログイン

        return redirect()->route('verification.notice');
    }

    // 認証案内画面
    public function verificationNotice()
    {
        return view('auth.verification_email');
    }

    // メールリンクで認証
    public function verifyEmail(EmailVerificationRequest $request)
    {
        $request->fulfill(); // 認証完了
        return redirect()->route('attendance.show'); // 認証後の遷移先
    }

    // 認証メール再送
    public function resendVerification(Request $request)
    {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('message', '認証メールを再送しました');
    }

    public function showLogin()
    {
        return view('auth/login');
    }

    public function login(LoginRequest $request)
    {
        $validatedData = $request->validated();

        if (Auth::attempt($validatedData)) {//emailとpass(hashcheck使用)が一致でtrue
            $request->session()->regenerate();//セッションIDを新しくすることで、ログイン前に使っていたセッションID悪用を防ぐ。
            $user = Auth::user();//今回は使っていないが、後で使うために書かれているケースが多い。

            return redirect()->intended('/attendance');//intendedログイン前にアクセスしようとしていたページ→なければ指定先へ遷移
        }

        return back()->withErrors(['login' => 'ログイン情報が登録されていません'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();//← ログイン情報を削除（認証解除）

        $request->session()->invalidate();// ← セッションIDを無効化（破棄） 古いセッションIDを無効化
        $request->session()->regenerateToken();//← CSRF対策トークンを再発行 偽フォーム・不正POSTを防ぐ

        return redirect('/login');
    }

    public function showAdminLogin()
    {
        return view('auth/admin_login');
    }

    public function adminLogin(LoginRequest $request)
    {
        $validatedData = $request->validated();

        // attemptで email & password が正しいかチェック
        if (Auth::attempt($validatedData)) {

            $request->session()->regenerate();

            // ログインしたユーザーを取得
            $user = Auth::user();

            // role が admin でなければログアウトして弾く
            if ($user->role !== 'admin') { //!== 左右の値が 値も型も 違う場合に true を返す
                Auth::logout();
                return back()->withErrors([
                    'login' => '管理者権限がありません' //要件にはないが
                ])->onlyInput('email');
            }

            // OK → 管理者ページへ
            return redirect()->route('admin.attendanceList.show');
        }

        return back()->withErrors([
            'login' => 'ログイン情報が登録されていません'
        ])->onlyInput('email');
    }

}
