<?php

namespace Tests\Feature\Admin;

use App\Enums\LoginForm;
use App\Enums\Role;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class GetAttendanceListTest extends TestCase
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
                'clock_in' => $now->copy()->format('Y-m-d 09:00:00'),
                'clock_out' => $now->copy()->format('Y-m-d 18:00:00'),
            ]);
            BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $now->copy()->format('Y-m-d 13:00:00'),
                    'clock_out' => $now->copy()->format('Y-m-d 14:00:00'),
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
        $response->assertSee($now->copy()->format('Y/m/d'));
        $count = 0;
        foreach ($users as $user) {
            $count++;
            $response->assertSeeInOrder([
            $user->name,
            '09:00', //出勤
            '18:00', // 退勤
            '1:00', // 休憩
            '8:00', // 合計
            ]);
        }
        $this->assertEquals(count($users),$count);
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
        $yesterday = now()->subDay();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $yesterday->copy()->format('Y-m-d'),
            'clock_in' => $yesterday->copy()->format('Y-m-d 09:00:00'),
            'clock_out' => $yesterday->copy()->format('Y-m-d 18:00:00'),
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'clock_in' => $yesterday->copy()->format('Y-m-d 12:00:00'),
            'clock_out' => $yesterday->copy()->format('Y-m-d 13:00:00'),
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
        $response =  $this->get(route('admin.index', ['date' => $yesterday->copy()->format('Ymd')]));
        $response->assertStatus(200);

        $response->assertSee($yesterday->copy()->format('Y/m/d'));
        $response->assertSeeInOrder([
        $user->name,
        '09:00',
        '18:00',
        '1:00',
        '8:00',
        ]);
    }

    /**
     * 「翌日」を押下した時に次の日の勤怠情報が表示される
     */
    #[Test]
    public function nextDateIsDisplayed()
    {
        $user = User::factory()->create();
        $tomorrow = now()->addDay();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $tomorrow->copy()->format('Y-m-d'),
            'clock_in' => $tomorrow->copy()->format('Y-m-d 18:30:00'),
            'clock_out' => $tomorrow->copy()->format('Y-m-d 23:30:00'),
        ]);
        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'clock_in' => $tomorrow->copy()->format('Y-m-d 22:30:00'),
            'clock_out' => $tomorrow->copy()->format('Y-m-d 23:00:00'),
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
        $response =  $this->get(route('admin.index', ['date' => $tomorrow->copy()->format('Ymd')]));
        $response->assertStatus(200);
        $response->assertSee($tomorrow->copy()->format('Y/m/d'));
        $response->assertSeeInOrder([
        $user->name,
        '18:30',
        '23:30',
        '0:30',
        '4:30',
        ]);
    }
}
