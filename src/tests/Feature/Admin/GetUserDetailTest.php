<?php

namespace Tests\Feature\Auth;

use App\Enums\LoginForm;
use App\Enums\Role;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
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
        $startOfMonth = now()->startOfMonth();
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfMonth->copy()->addDays($i);
            $attendance = Attendance::factory()->create([
                'user_id' => $user->id,
                'work_date' => $date->copy()->format('Y-m-d'),
                'clock_in' => $date->copy()->format('Y-m-d H:i:s'), // 0:00
                'clock_out' => $date->copy()->addHours(8)->format('Y-m-d H:i:s'), // 8:00
            ]);
            BreakTime::factory()->create([
                        'attendance_id' => $attendance->id,
                        'clock_in' => $date->copy()->addHours(5)->format('Y-m-d H:i:s'), // 5:00
                        'clock_out' => $date->copy()->addHours(5.5)->format('Y-m-d H:i:s'), // 5:30
            ]);
            BreakTime::factory()->create([
                        'attendance_id' => $attendance->id,
                        'clock_in' => $date->copy()->addHours(6)->format('Y-m-d H:i:s'), // 6:00
                        'clock_out' => $date->copy()->addHours(7)->format('Y-m-d H:i:s'), // 7:00
            ]);
        }

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

        // 勤怠情報が正確に表示される
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfMonth->copy()->addDays($i);
            $response->assertSeeInOrder([
                $date->copy()->format('m/d') . '(' . $date->copy()->isoFormat('ddd') . ')',
                    '0:00', // 出勤
                    '08:00', // 退勤
                    '1:30', // 休憩
                    '6:30', // 合計
            ]);
        }
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    #[Test]
    public function previousMonthIsDisplayed()
    {
        $user = User::factory()->create();
        $startOfMonth = now()->startOfMonth()->subMonth();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $startOfMonth->copy()->format('Y-m-d'),
            'clock_in' => $startOfMonth->copy()->format('Y-m-d H:i:s'), // 0:00
            'clock_out' => $startOfMonth->copy()->addHours(8)->format('Y-m-d H:i:s'), // 8:00
        ]);
        BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $startOfMonth->copy()->addHours(3)->format('Y-m-d H:i:s'), // 3:00
                    'clock_out' => $startOfMonth->copy()->addHours(4)->format('Y-m-d H:i:s'), // 4:00
        ]);
        BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $startOfMonth->copy()->addHours(5)->format('Y-m-d H:i:s'), // 5:00
                    'clock_out' => $startOfMonth->copy()->addHours(6)->format('Y-m-d H:i:s'), // 6:00
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
        $response =  $this->get(route('admin.monthly.index', ['id' => $user->id, 'date' => $startOfMonth->copy()->format('Ym')]));
        $response->assertSee($startOfMonth->copy()->format('Ym'));
        $response->assertSeeInOrder([
                        $startOfMonth->copy()->format('m/d') . '(' . $startOfMonth->copy()->isoFormat('ddd') . ')',
                        '0:00', // 出勤
                        '08:00', // 退勤
                        '2:00', // 休憩
                        '6:00', // 合計
                    ]);
    }

    /**
     * 「翌月」を押下した時に表示月の前(翌)月の情報が表示される
     */
    #[Test]
    public function nextMonthIsDisplayed()
    {
        $user = User::factory()->create();
        $nextOfMonth = now()->startOfMonth()->addMonth();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $nextOfMonth->copy()->format('Y-m-d'),
            'clock_in' => $nextOfMonth->copy()->format('Y-m-d H:i:s'), // 0:00
            'clock_out' => $nextOfMonth->copy()->addHours(8)->format('Y-m-d H:i:s'), // 8:00
        ]);
        BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $nextOfMonth->copy()->addHours(3.5)->format('Y-m-d H:i:s'), // 3:30
                    'clock_out' => $nextOfMonth->copy()->addHours(4)->format('Y-m-d H:i:s'), // 4:00
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
        $response =  $this->get(route('admin.monthly.index', ['id' => $user->id, 'date' => $nextOfMonth->copy()->format('Ym')]));
        $response->assertSee($nextOfMonth->copy()->format('Ym'));
        $response->assertSeeInOrder([
                        $nextOfMonth->copy()->format('m/d') . '(' . $nextOfMonth->copy()->isoFormat('ddd') . ')',
                        '0:00', // 出勤
                        '08:00', // 退勤
                        '0:30', // 休憩
                        '7:30', // 合計
                    ]);
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    #[Test]
    public function adminCanAccessShowPage()
    {
        $user = User::factory()->create();
        $startOfMonth = now()->startOfMonth();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $startOfMonth->copy()->format('Y-m-d'),
            'clock_in' => $startOfMonth->copy()->addHour()->format('Y-m-d H:i:s'), // 1:00
            'clock_out' => null,
        ]);
        BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $startOfMonth->copy()->addHours(6)->format('Y-m-d H:i:s'), // 6:00
                    'clock_out' => null,
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
        $response->assertSeeInOrder([
            $user->name,
            $startOfMonth->copy()->format('Y年'),
            $startOfMonth->copy()->format('n月'),
            $startOfMonth->copy()->format('j日'),
            '01:00',
            null,
            '06:00',
            null,
        ]);
    }
}
