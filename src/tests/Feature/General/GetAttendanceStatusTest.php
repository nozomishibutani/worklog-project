<?php

namespace Tests\Feature\Auth;

use App\Enums\attendanceStatus;
use App\Enums\LoginForm;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class GetAttendanceStatusTest extends TestCase
{
    /**
     * ステータス確認機能
     */
    use RefreshDatabase;

    /**
     * 勤務外の場合、勤怠ステータスが正しく表示される
     */
    #[Test]
    public function offStatusIsDisplayedInUiFormat() {
        $user = User::factory()->create();
        session([
                'user_id' => $user->id,
                'login_form' => LoginForm::GENERAL->value,
            ]);

        // 1. ステータスが勤務外のユーザーにログインする
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
        ]);

        // 2. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get(route('index'));
        $response->assertStatus(200);

        // 3. 画面に表示されているステータスを確認する
        $response->assertSee(attendanceStatus::OFF->label());
    }

    /**
     * 出勤中の場合、勤怠ステータスが正しく表示される
     */
    #[Test]
    public function onDutyIsDisplayedInUiFormat() {
        $user = User::factory()->create();
        session([
                'user_id' => $user->id,
                'login_form' => LoginForm::GENERAL->value,
            ]);

        $now = now();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => $now->format('Y-m-d H:i:s'),
        ]);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => $now->format('Y-m-d'),
            'clock_in' => $now->format('Y-m-d H:i:s'),
        ]);

        // 1. ステータスが出勤中のユーザーにログインする
        // 2. 勤怠打刻画面を開く
        $response = $this->actingAs($user)->get(route('index'));
        $response->assertStatus(200);

        // 3. 画面に表示されているステータスを確認する
        $response->assertSee(attendanceStatus::ON_DUTY->label());
    }
}