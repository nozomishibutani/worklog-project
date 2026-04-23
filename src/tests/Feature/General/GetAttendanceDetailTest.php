<?php

namespace Tests\Feature\Auth;

use App\Enums\LoginForm;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use App\Services\AttendanceCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class GetAttendanceDetailTest extends TestCase
{
    /**
     * 勤怠詳細情報取得機能（一般ユーザー）
     */
    use RefreshDatabase;

    /**
     *  勤怠詳細画面の「名前」がログインユーザーの氏名になっている
     *  勤怠詳細画面の「日付」が選択した日付になっている
     * 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
     *  「休憩」にて記されている時間がログインユーザーの打刻と一致している
     */
    #[Test]
    public function displayDataIsAccurate()
    {
        $user = User::factory()->create();
        $now = now();

        // 1. 勤怠情報が登録されたユーザーにログインをする
        $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'work_date' => $now->copy()->format('Y-m-d'),
                    'clock_in' => $now->copy()->subHour()->format('Y-m-d H:i:s'),
                    'clock_out' => $now->copy()->format('Y-m-d H:i:s'),
                ]);

        BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $now->copy()->subMinutes(35)->format('Y-m-d H:i:s'),
                    'clock_out' => $now->copy()->subMinutes(20)->format('Y-m-d H:i:s'),
                ]);

        BreakTime::factory()->create([
                            'attendance_id' => $attendance->id,
                            'clock_in' => $now->copy()->subMinutes(50)->format('Y-m-d H:i:s'),
                            'clock_out' => $now->copy()->subMinutes(55)->format('Y-m-d H:i:s'),
                        ]);

        session([
            'user_id' => $user->id,
            'login_form' => LoginForm::GENERAL->value,
        ]);
        $this->actingAs($user);

        // 2. 勤怠詳細ページを開く
        $response =  $this->get(route('show', ['id' => $attendance->id]));
        $response->assertStatus(200);

        $attendanceCalculatorService = app(AttendanceCalculatorService::class);
        [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
            'workDate' => $workDate,
            'currentAttendanceStatus' => $currentAttendanceStatus,
            'note' => $note,
        ]
        = $attendanceCalculatorService->getUserDailyAttendance($attendance->id, null);

        // 3. 名前欄を確認する
        // 名前がログインユーザーの名前になっている
        $response->assertSee($user->name);
        $this->assertEquals($user->name, $workTimes['name']);

        // 3. 日付欄を確認する
        // 日付が選択した日付になっている
        $response->assertSee($workDate);
        $this->assertTrue(
            Carbon::parse($workDate['year'] . '-' .$workDate['month'] . '-' .$workDate['day'])->equalTo(Carbon::parse($attendance->work_date))
        );

        // 3. 出勤・退勤欄を確認する
        //「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
        $response->assertSee($workTimes['clock_in']);
        $response->assertSee($workTimes['clock_out']);
        $this->assertEquals($workTimes['clock_in'], ($attendance->clock_in)->format('H:i'));
        $this->assertEquals($workTimes['clock_out'], ($attendance->clock_out)->format('H:i'));

        // 3. 休憩欄を確認する
        //「休憩」にて記されている時間がログインユーザーの打刻と一致している
        foreach ($breakTimes as $breakTime) {
            $response->assertSee($breakTime['clock_in']);
            $response->assertSee($breakTime['clock_out']);
            $tmp = BreakTime::find($breakTime['id']);
            $this->assertEquals($breakTime['clock_in'], ($tmp->clock_in)->format('H:i'));
            $this->assertEquals($breakTime['clock_out'], ($tmp->clock_out)->format('H:i'));
        }
    }
}
