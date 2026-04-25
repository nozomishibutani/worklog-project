<?php

namespace Tests\Feature\Auth;

use App\Enums\LoginForm;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class GetAttendanceListTest extends TestCase
{
    /**
     * 勤怠一覧情報取得機能（一般ユーザー）
     */
    use RefreshDatabase;

    /**
     * 自分が行った勤怠情報が全て表示されている
     */
    #[Test]
    public function AllAttendanceAreDisplayed()
    {
        $user = User::factory()->create();
        $startOfMonth = now()->startOfMonth();

        for ($i = 0; $i < 7; $i++) {
            $date = $startOfMonth->copy()->addDays($i);
            $attendance = Attendance::factory()->create([
                'user_id' => $user->id,
                'work_date' => $date->format('Y-m-d'),
                'clock_in' => $date->copy()->format('Y-m-d H:i:s'), // 0:00
                'clock_out' => $date->copy()->addHours(8)->format('Y-m-d H:i:s'), // 8:00
            ]);

            BreakTime::factory()->create([
                'attendance_id' => $attendance->id,
                'clock_in' => $date->copy()->addHours(6)->format('Y-m-d H:i:s'), // 6:00
                'clock_out' => $date->copy()->addHours(7)->format('Y-m-d H:i:s'), // 7:00
            ]);
        }

        // 1. 勤怠情報が登録されたユーザーにログインする
        session([
            'user_id' => $user->id,
            'login_form' => LoginForm::GENERAL->value,
        ]);
        $this->actingAs($user);


        // 2. 勤怠一覧ページを開く
        $response =  $this->get(route('monthly.index'));
        $response->assertStatus(200);

        // 3. 自分の勤怠情報がすべて表示されていることを確認する
        // 自分の勤怠情報が全て表示されている
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfMonth->copy()->addDays($i);
            $response->assertSeeInOrder([
                $date->copy()->format('m/d') . '(' . $date->copy()->isoFormat('ddd') . ')',
                '0:00', // 出勤
                '08:00', // 退勤
                '1:00', // 休憩
                '7:00', // 合計
            ]);
        }
    }

    /**
     * 勤怠一覧画面に遷移した際に現在の月が表示される
     */
    #[Test]
    public function currentMonthIsDisplayed()
    {
        $user = User::factory()->create();
        $now = now();

        // 1. ユーザーにログインする
        session([
            'user_id' => $user->id,
            'login_form' => LoginForm::GENERAL->value,
        ]);
        $this->actingAs($user);

        // 2. 勤怠一覧画面ページを開く
        $response =  $this->get(route('monthly.index'));
        $response->assertStatus(200);

        // 現在の月が表示されている
        $response->assertSee($now->format('Y/m'));
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    #[Test]
    public function previousMonthIsDisplayed()
    {
        $user = User::factory()->create();
        $startOfPreviousMonth = now()->startOfMonth()->subMonth();

        // 1. 勤怠情報が登録されたユーザーにログインをする
        $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'work_date' => $startOfPreviousMonth->copy()->format('Y-m-d'),
                    'clock_in' => $startOfPreviousMonth->copy()->format('Y-m-d H:i:s'), // 0:00
                    'clock_out' => $startOfPreviousMonth->copy()->addHours(8)->format('Y-m-d H:i:s'), // 8:00
                ]);

        BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $startOfPreviousMonth->copy()->addHours(6)->format('Y-m-d H:i:s'), // 6:00
                    'clock_out' => $startOfPreviousMonth->copy()->addHours(7)->format('Y-m-d H:i:s'), //7:00
                ]);

        session([
            'user_id' => $user->id,
            'login_form' => LoginForm::GENERAL->value,
        ]);
        $this->actingAs($user);

        // 2. 勤怠一覧ページを開く
        $response =  $this->get(route('monthly.index'));
        $response->assertStatus(200);

        // 3. 「前月」ボタンを押す
        // 前月の情報が表示されている
        $response =  $this->get(route('monthly.index', ['id' => $user->id, 'date' => $startOfPreviousMonth->copy()->format('Ym')]));
        $response->assertSee($startOfPreviousMonth->copy()->format('Ym'));
        $response->assertSeeInOrder([
            $startOfPreviousMonth->copy()->format('m/d') . '(' . $startOfPreviousMonth->copy()->isoFormat('ddd') . ')',
            '0:00', // 出勤
            '08:00', // 退勤
            '1:00', // 休憩
            '7:00', // 合計
        ]);
    }

    /**
     * 「翌月」を押下した時に表示月の前（翌）月の情報が表示される
     */
    #[Test]
    public function nextMonthIsDisplayed()
    {
        $user = User::factory()->create();
        $startOfNextMonth = now()->startOfMonth()->addMonth();

        // 1. 勤怠情報が登録されたユーザーにログインをする
        $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'work_date' => $startOfNextMonth->copy()->format('Y-m-d'),
                    'clock_in' =>  $startOfNextMonth->copy()->format('Y-m-d H:i:s'), // 0:00
                    'clock_out' => $startOfNextMonth->copy()->addHours(8.5)->format('Y-m-d H:i:s'), // 8:30
                ]);

        BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' =>  $startOfNextMonth->copy()->addHours(1)->format('Y-m-d H:i:s'), // 1:00
                    'clock_out' => $startOfNextMonth->copy()->addHours(3)->format('Y-m-d H:i:s'), // 3:00
                ]);

        session([
            'user_id' => $user->id,
            'login_form' => LoginForm::GENERAL->value,
        ]);
        $this->actingAs($user);

        // 2. 勤怠一覧ページを開く
        $response =  $this->get(route('monthly.index'));
        $response->assertStatus(200);

        // 3. 「翌月」ボタンを押す
        // 翌月の情報が表示されている
        $response =  $this->get(route('monthly.index', ['id' => $user->id, 'date' =>  $startOfNextMonth->copy()->format('Ym')]));
        $response->assertSee($startOfNextMonth->copy()->format('Ym'));

        $response->assertSeeInOrder([
            $startOfNextMonth->copy()->format('m/d') . '(' . $startOfNextMonth->copy()->isoFormat('ddd') . ')',
            '0:00', // 出勤
            '08:30', // 退勤
            '2:00', // 休憩
            '6:30', // 合計
        ]);
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    #[Test]
    public function userCanAccessShowPage()
    {
        $user = User::factory()->create();
        $startOfMonth = now()->startOfMonth();

        // 1. 勤怠情報が登録されたユーザーにログインをする
        $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'work_date' => $startOfMonth->copy()->format('Y-m-d'),
                    'clock_in' => $startOfMonth->copy()->format('Y-m-d H:i:s'), // 0:00
                    'clock_out' => $startOfMonth->copy()->addHours(8)->format('Y-m-d H:i:s'), // 8:00
                ]);

        BreakTime::factory()->create([
                    'attendance_id' => $attendance->id,
                    'clock_in' => $startOfMonth->copy()->addHours(2)->format('Y-m-d H:i:s'), // 2:00
                    'clock_out' => $startOfMonth->copy()->addHours(2.5)->format('Y-m-d H:i:s'), // 2:30
                ]);

        session([
            'user_id' => $user->id,
            'login_form' => LoginForm::GENERAL->value,
        ]);
        $this->actingAs($user);

        // 2. 勤怠一覧ページを開く
        $response =  $this->get(route('monthly.index'));
        $response->assertStatus(200);

        // 3. 「詳細」ボタンを押下する
        $response =  $this->get(route('show', ['id' => $attendance->id]));
        $response->assertStatus(200);

        // その日の勤怠詳細画面に遷移する
        $response->assertSee($startOfMonth->copy()->year . '年');
        $response->assertSee($startOfMonth->copy()->month. '月');
        $response->assertSee($startOfMonth->copy()->day. '日');
        $response->assertSee('0:00'); // 出勤
        $response->assertSee('08:00'); // 退勤
        $response->assertSee('02:00'); // 休憩入
        $response->assertSee('02:30'); // 休憩戻
    }
}
