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

class GetUserDetailTest extends TestCase
{
    /**
     * ユーザー情報取得機能（管理者）
     */
    use RefreshDatabase;

    /**
     * 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
     */
    #[Test]
    public function adminCanGetAllUserDetail()
    {
        $users = User::factory(10)->create();

        // 1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        $this->actingAs($admin);

        // 2. スタッフ一覧ページを開く
        $response =  $this->get(route('admin.user.index'));
        $response->assertStatus(200);

        // 全ての一般ユーザーの氏名とメールアドレスが正しく表示されている
        $count = 0;
        foreach ($users as $user) {
            $count++;
            $response->assertSeeInOrder([
                $user->name,
                $user->email,
            ]);
        }
        $this->assertEquals(count($users), $count);
    }

    /**
     * ユーザーの勤怠情報が正しく表示される
     */
    #[Test]
    public function userAttendanceIsDisplayAccurately()
    {
        $user = User::factory()->create();
        $now = now();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $now->copy()->format('Y-m-d'),
            'clock_in' => $now->copy()->subHour()->format('Y-m-d H:i:s'),
            'clock_out' => $now->copy()->format('Y-m-d H:i:s'),
        ]);
        BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $now->copy()->subMinutes(45)->format('Y-m-d H:i:s'),
                    'clock_out' => $now->copy()->subMinutes(20)->format('Y-m-d H:i:s'),
        ]);
        BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $now->copy()->subMinutes(15)->format('Y-m-d H:i:s'),
                    'clock_out' => $now->copy()->subMinutes(5)->format('Y-m-d H:i:s'),
        ]);
        $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'work_date' => $now->copy()->subDay()->format('Y-m-d'),
                    'clock_in' => $now->copy()->subHour()->format('Y-m-d H:i:s'),
                    'clock_out' => $now->copy()->format('Y-m-d H:i:s'),
                ]);

        // 1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        $this->actingAs($admin);

        // 2. 選択したユーザーの勤怠一覧ページを開く
        $response =  $this->get(route('admin.monthly.index', ['id' => $user->id]));
        $response->assertStatus(200);

        $attendanceCalculatorService = app(AttendanceCalculatorService::class);
        $startOfMonth = Carbon::createFromFormat('Ymd', $now->copy()->format('Ym') . '01')->startOfMonth();
        [
            'name' => $name,
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
        ] = $attendanceCalculatorService->getUserMonthlyAttendances($user->id, $startOfMonth);

        // 勤怠情報が正確に表示される
        foreach ($workTimes as $workTime) {
            $response->assertSee($workTime['display_date']);
            $response->assertSee($workTime['clock_in']);
            $response->assertSee($workTime['clock_out']);
            if (isset($breakTimes[$now->copy()->format('Ymd')]['display_total'])) {
                $response->assertSee($breakTimes[$now->copy()->format('Ymd')]['display_total']);
            }
            $response->assertSee($workTime['display_total']);
        }
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    #[Test]
    public function previousMonthIsDisplayed()
    {
        $user = User::factory()->create();
        $now = now();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $now->copy()->startOfMonth()->subMonth()->format('Y-m-d'),
            'clock_in' => $now->copy()->startOfMonth()->subMonth()->format('Y-m-d H:i:s'),
            'clock_out' => $now->copy()->startOfMonth()->subMonth()->addHour(6)->format('Y-m-d H:i:s'),
        ]);
        BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $now->copy()->startOfMonth()->subMonth()->addHours(3)->format('Y-m-d H:i:s'),
                    'clock_out' => $now->copy()->startOfMonth()->subMonth()->addHours(4)->format('Y-m-d H:i:s'),
        ]);
        BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $now->copy()->startOfMonth()->subMonth()->addHours(4.5)->format('Y-m-d H:i:s'),
                    'clock_out' => $now->copy()->startOfMonth()->subMonth()->addHours(5)->format('Y-m-d H:i:s'),
        ]);

        // 1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        $this->actingAs($admin);

        // 2. 勤怠一覧ページを開く
        $response =  $this->get(route('admin.monthly.index', ['id' => $user->id]));
        $response->assertStatus(200);

        // 3. 「前月」ボタンを押す
        // 前月の情報が表示されている
        $response =  $this->get(route('admin.monthly.index', ['id' => $user->id, 'date' => $now->copy()->startOfMonth()->subMonth()->format('Ym')]));
        $response->assertSee($now->copy()->startOfMonth()->subMonth()->format('Ym'));

        $attendanceCalculatorService = app(AttendanceCalculatorService::class);
        [
            'name' => $name,
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
        ] = $attendanceCalculatorService->getUserMonthlyAttendances($user->id, $now->copy()->startOfMonth()->subMonth());

        foreach ($workTimes as $workTime) {
            $response->assertSee($workTime['display_date']);
            $response->assertSee($workTime['clock_in']);
            $response->assertSee($workTime['clock_out']);
            if (isset($breakTimes[$now->copy()->startOfMonth()->subMonth()->format('Ymd')]['display_total'])) {
                $response->assertSee($breakTimes[$now->copy()->startOfMonth()->subMonth()->format('Ymd')]['display_total']);
            }
            $response->assertSee($workTime['display_total']);
        }
    }

    /**
     * 「翌月」を押下した時に表示月の前(翌)月の情報が表示される
     */
    #[Test]
    public function nextMonthIsDisplayed()
    {
        $user = User::factory()->create();
        $now = now();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $now->copy()->startOfMonth()->addMonth()->format('Y-m-d'),
            'clock_in' => $now->copy()->startOfMonth()->format('Y-m-d H:i:s'),
            'clock_out' => $now->copy()->startOfMonth()->addHours(6.5)->format('Y-m-d H:i:s'),
        ]);
        BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $now->copy()->startOfMonth()->addHours(0.5)->format('Y-m-d H:i:s'),
                    'clock_out' => $now->copy()->startOfMonth()->addHours(2)->format('Y-m-d H:i:s'),
        ]);

        // 1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        $this->actingAs($admin);

        // 2. 勤怠一覧ページを開く
        $response =  $this->get(route('admin.monthly.index', ['id' => $user->id]));
        $response->assertStatus(200);

        // 3. 「翌月」ボタンを押す
        // 翌月の情報が表示されている
        $response =  $this->get(route('admin.monthly.index', ['id' => $user->id, 'date' => $now->copy()->startOfMonth()->addMonth()->format('Ym')]));
        $response->assertSee($now->copy()->addMonth()->format('Ym'));

        $attendanceCalculatorService = app(AttendanceCalculatorService::class);
        [
            'name' => $name,
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
        ] = $attendanceCalculatorService->getUserMonthlyAttendances($user->id, $now->copy()->startOfMonth()->addMonth());

        foreach ($workTimes as $workTime) {
            $response->assertSee($workTime['display_date']);
            $response->assertSee($workTime['clock_in']);
            $response->assertSee($workTime['clock_out']);
            if (isset($breakTimes[$now->copy()->subMonth()->format('Ymd')]['display_total'])) {
                $response->assertSee($breakTimes[$now->copy()->subMonth()->format('Ymd')]['display_total']);
            }
            $response->assertSee($workTime['display_total']);
        }
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    #[Test]
    public function adminCanAccessShowPage()
    {
        $user = User::factory()->create();
        $now = now();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $now->copy()->format('Y-m-d'),
            'clock_in' => $now->copy()->subHour()->format('Y-m-d H:i:s'),
            'clock_out' => $now->copy()->format('Y-m-d H:i:s'),
        ]);

        // 1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        $this->actingAs($admin);

        // 2. 勤怠一覧ページを開く
        $response =  $this->get(route('admin.monthly.index', ['id' => $user->id]));
        $response->assertStatus(200);

        // 3. 「詳細」ボタンを押下する
        $response =  $this->get(route('admin.show', ['id' => $attendance->id]));
        $response->assertStatus(200);

        // その日の勤怠詳細画面に遷移する
        $response->assertSee($now->year . '年');
        $response->assertSee($now->month. '月');
        $response->assertSee($now->day. '日');
    }
}
