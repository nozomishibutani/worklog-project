<?php

namespace Tests\Feature\General;

use App\Enums\LoginForm;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
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
        $startOfMonth = now()->startOfMonth();

        // 1. 勤怠情報が登録されたユーザーにログインをする
        $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'work_date' => $startOfMonth->copy()->format('Y-m-d'),
                    'clock_in' => $startOfMonth->copy()->format('Y-m-d H:i:s'),
                    'clock_out' => $startOfMonth->copy()->addHour(8)->format('Y-m-d H:i:s'),
                ]);

        $breakTime = BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $startOfMonth->copy()->addHour(5)->format('Y-m-d H:i:s'),
                    'clock_out' => $startOfMonth->copy()->addHour(6)->format('Y-m-d H:i:s'),
                ]);
        session([
            'user_id' => $user->id,
            'login_form' => LoginForm::GENERAL->value,
        ]);
        $this->actingAs($user);

        // 2. 勤怠詳細ページを開く
        $response =  $this->get(route('show', ['id' => $attendance->id]));
        $response->assertStatus(200);

        // 3. 名前欄を確認する
        // 名前がログインユーザーの名前になっている
        $response->assertSee($user->name);

        // 3. 日付欄を確認する
        // 日付が選択した日付になっている
        $response->assertSee(Carbon::parse($attendance->work_date)->format('Y年'));
        $response->assertSee(Carbon::parse($attendance->work_date)->format('n月'));
        $response->assertSee(Carbon::parse($attendance->work_date)->format('j日'));

        // 3. 出勤・退勤欄を確認する
        //「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
        $response->assertSee(Carbon::parse($attendance->clock_in)->format('H:i'));
        $response->assertSee(Carbon::parse($attendance->clock_out)->format('H:i'));

        // 3. 休憩欄を確認する
        //「休憩」にて記されている時間がログインユーザーの打刻と一致している
        $response->assertSee(Carbon::parse($breakTime->clock_in)->format('H:i'));
        $response->assertSee(Carbon::parse($breakTime->clock_out)->format('H:i'));
    }
}
