<?php

namespace Tests\Feature\Auth;

use App\Enums\LoginForm;
use App\Enums\Role;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use App\Services\AttendanceCalculatorService;
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
    public function allUserAttendancesAreDisplayedAccurately()
    {
        $users = User::factory(3)->create();
        $now = now();

        foreach ($users as $user) {
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

        $attendanceCalculatorService = app(AttendanceCalculatorService::class);
        [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
        ]
        = $attendanceCalculatorService->getUserDailyAttendances(carbon::today());

        // その日の全ユーザーの勤怠情報が正確な値になっている
        $count = 0;
        $response->assertSee($now->copy()->format('Y/m/d'));
        foreach ($workTimes as $userId => $workTime) {
            $count++;
            $response->assertSeeInOrder([
            $workTime['name'],
            $workTime['clock_in'],
            $workTime['clock_out'],
            $breakTimes[$userId]['display_total'],
            $workTime['display_total'],
            ]);
        }
        $this->assertEquals(count($users), $count);
    }

    /**
     * 遷移した際に現在の日付が表示される
     */
    #[Test]
    public function currentDateIsDisplayed()
    {
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

        // 勤怠一覧画面にその日の日付が表示されている
        $response->assertSee(now()->copy()->format('Y/m/d'));
    }

    /**
     * 「前日」を押下した時に前の日の勤怠情報が表示される
     */
    #[Test]
    public function previousDateIsDisplayed()
    {
        $user = User::factory()->create();
        $now = now();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $now->copy()->subDay()->format('Y-m-d'),
            'clock_in' => $now->copy()->subDay()->format('Y-m-d H:i:s'),
            'clock_out' => $now->copy()->subDay()->addHours(1)->format('Y-m-d H:i:s'),
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'clock_in' => $now->copy()->subDay()->addMinutes(10)->format('Y-m-d H:i:s'),
            'clock_out' => $now->copy()->subDay()->addMinutes(30)->format('Y-m-d H:i:s'),
        ]);

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

        // 3. 「前日」ボタンを押す
        // 前日の日付の勤怠情報が表示される
        $response =  $this->get(route('admin.index', ['date' => $now->copy()->subDay()->format('Ymd')]));
        $response->assertSee($now->copy()->subDay()->format('Y/m/d'));

        $attendanceCalculatorService = app(AttendanceCalculatorService::class);
        [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
        ]
        = $attendanceCalculatorService->getUserDailyAttendances(Carbon::parse($now->copy()->subDay()->format('Y-m-d')));

        foreach ($workTimes as $workTime) {
            $response->assertSeeInOrder([
            $user->name,
            $workTime['clock_in'],
            $workTime['clock_out'],
            $breakTimes[$user->id]['display_total'],
            $workTime['display_total'],
            ]);
        }
    }

    /**
     * 「翌日」を押下した時に次の日の勤怠情報が表示される
     */
    #[Test]
    public function nextDateIsDisplayed()
    {
        $user = User::factory()->create();
        $now = now();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $now->copy()->subDay()->format('Y-m-d'),
            'clock_in' => $now->copy()->subDay()->format('Y-m-d H:i:s'),
            'clock_out' => $now->copy()->subDay()->addHours(1)->format('Y-m-d H:i:s'),
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'clock_in' => $now->copy()->subDay()->addMinutes(10)->format('Y-m-d H:i:s'),
            'clock_out' => $now->copy()->subDay()->addMinutes(30)->format('Y-m-d H:i:s'),
        ]);

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

        // 3. 「翌日」ボタンを押す
        // 翌日の日付の勤怠情報が表示される
        $response =  $this->get(route('admin.index', ['date' => $now->copy()->addDay()->format('Ymd')]));
        $response->assertSee($now->copy()->addDay()->format('Y/m/d'));

        $attendanceCalculatorService = app(AttendanceCalculatorService::class);
        [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
        ]
        = $attendanceCalculatorService->getUserDailyAttendances(Carbon::parse($now->copy()->addDay()->format('Y-m-d')));

        foreach ($workTimes as $workTime) {
            $response->assertSeeInOrder([
            $user->name,
            $workTime['clock_in'],
            $workTime['clock_out'],
            $breakTimes[$user->id]['display_total'],
            $workTime['display_total'],
            ]);
        }
    }
}
