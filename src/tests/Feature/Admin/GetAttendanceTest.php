<?php

namespace Tests\Feature\Auth;

use App\Enums\ApprovalStatus;
use App\Enums\LoginForm;
use App\Enums\Role;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use App\Services\AttendanceCalculatorService;
use App\Services\AttendanceUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class GetAttendanceTest extends TestCase
{
    /**
     * 勤怠一覧情報取得機能（管理者）
     */
    use RefreshDatabase;

    /**
     * その日になされた全ユーザーの勤怠情報が正確に確認できる
     */
    #[Test]
    public function isClockInAfterClockOut()
    {
        $users = User::factory(3)->create();
        $now = now();

        foreach($users as $user){
            $attendance = Attendance::factory()->create([
                'user_id' => $user->id,
                'clock_in' => $now->copy()->subHours($user->id)->format('Y-m-d H:i:s'),
                'clock_out' => $now->copy()->format('Y-m-d H:i:s'),
            ]);
            BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $now->copy()->subMinutes(35)->format('Y-m-d H:i:s'),
                    'clock_out' => $now->copy()->subMinutes(20)->format('Y-m-d H:i:s'),
            ]);
        }

        // 1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        $this->actingAs($admin);

        // 2. 勤怠一覧画面を開く
        $response =  $this->get(route('admin.index'));
        $response->assertStatus(200);

        // その日の全ユーザーの勤怠情報が正確な値になっている
        foreach(){
            
        }
        }
}