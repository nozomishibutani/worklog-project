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

class GetAttendanceDetailAndEditTest extends TestCase
{
    /**
     * 勤怠詳細情報取得・修正機能（管理者）
     */
    use RefreshDatabase;

    /**
     * 勤怠詳細画面に表示されるデータが選択したものになっている
     */
    #[Test]
    public function displayDataIsAccurate()
    {
        $user = User::factory()->create();
        $startOfMonth = now()->startOfMonth();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $startOfMonth->copy()->format('Y-m-d'),
            'clock_in' => $startOfMonth->copy()->format('Y-m-d H:i:s'), // 0:00
            'clock_out' => $startOfMonth->copy()->addHours(8)->format('Y-m-d H:i:s'), // 8:00
        ]);
        $breakTime = BreakTime::factory()->create([
                'attendance_id' => $attendance->id,
                'clock_in' => $startOfMonth->copy()->addHours(6)->format('Y-m-d H:i:s'), // 6:00
                'clock_out' => $startOfMonth->copy()->addHours(7)->format('Y-m-d H:i:s'), // 7:00
        ]);

        // 1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        $this->actingAs($admin);
        // 2. 勤怠詳細ページを開く
        $response =  $this->get(route('admin.show', ['id' => $attendance->id]));
        $response->assertStatus(200);

        // 詳細画面の内容が選択した情報と一致する
        // 日付け
        $response->assertSee($startOfMonth->copy()->format('Y年'));
        $response->assertSee($startOfMonth->copy()->format('n月'));
        $response->assertSee($startOfMonth->copy()->format('j日'));

        // 出勤・退勤
        $response->assertSee('00:00');
        $response->assertSee('08:00');
        $this->assertEquals('00:00', ($attendance->clock_in)->format('H:i'));
        $this->assertEquals('08:00', ($attendance->clock_out)->format('H:i'));

        // 休憩
        $response->assertSee('6:00');
        $response->assertSee('7:00');
        $this->assertEquals('6:00', ($breakTime['clock_in'])->format('G:i'));
        $this->assertEquals('7:00', ($breakTime['clock_out'])->format('G:i'));
    }

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    #[Test]
    public function isClockInAfterClockOut()
    {
        $user = User::factory()->create();
        $startOfMonth = now()->startOfMonth();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $startOfMonth->copy()->format('Y-m-d'),
            'clock_in' => $startOfMonth->copy()->format('Y-m-d H:i:s'), // 0:00
            'clock_out' => $startOfMonth->copy()->addHours(8)->format('Y-m-d H:i:s'), // 8:00
        ]);
        $breakTime = BreakTime::factory()->create([
                'attendance_id' => $attendance->id,
                'clock_in' => $startOfMonth->copy()->addHours(6)->format('Y-m-d H:i:s'), // 6:00
                'clock_out' => $startOfMonth->copy()->addHours(7)->format('Y-m-d H:i:s'), // 7:00
        ]);

        // 1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        $this->actingAs($admin);

        // 2. 勤怠詳細ページを開く
        $response =  $this->get(route('admin.show', ['id' => $attendance->id]));
        $response->assertStatus(200);

        // 3. 出勤時間を退勤時間より後に設定する
        // 4. 保存処理をする
        $response =  $this->post(route('admin.update'), [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'current_attendance_status' => null,
            'year' => $startOfMonth->copy()->format('Y'),
            'month' => $startOfMonth->copy()->format('m'),
            'day' => $startOfMonth->copy()->format('d'),
            'work_in' => $startOfMonth->copy()->addHours(8)->format('H:i'), // 8:00
            'work_out' => $startOfMonth->copy()->addHours(7)->format('H:i'), // 7:00
            'break_in' => [
                0 => null,
                $breakTime->id => null,
            ],
            'break_out' => [
                0 => null,
                $breakTime->id => null,
            ],
            'note' => '備考',
        ]);

        //「出勤時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'work_in' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /**
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    #[Test]
    public function isBreakInAfterClockOut()
    {
        $user = User::factory()->create();
        $startOfMonth = now()->startOfMonth();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $startOfMonth->copy()->format('Y-m-d'),
            'clock_in' => $startOfMonth->copy()->format('Y-m-d H:i:s'), // 0:00
            'clock_out' => $startOfMonth->copy()->addHours(8)->format('Y-m-d H:i:s'), // 8:00
        ]);
        $breakTime = BreakTime::factory()->create([
                'attendance_id' => $attendance->id,
                'clock_in' => $startOfMonth->copy()->addHours(6)->format('Y-m-d H:i:s'), // 6:00
                'clock_out' => $startOfMonth->copy()->addHours(7)->format('Y-m-d H:i:s'), // 7:00
        ]);

        // 1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        $this->actingAs($admin);

        // 2. 勤怠詳細ページを開く
        $response =  $this->get(route('admin.show', ['id' => $attendance->id]));
        $response->assertStatus(200);

        // 3. 休憩開始時間を退勤時間より後に設定する
        // 4. 保存処理をする
        $response =  $this->post(route('admin.update'), [
                    'attendance_id' => $attendance->id,
                    'user_id' => $user->id,
                    'current_attendance_status' => null,
                    'year' => $startOfMonth->copy()->format('Y'),
                    'month' => $startOfMonth->copy()->format('m'),
                    'day' => $startOfMonth->copy()->format('d'),
                    'work_in' => $startOfMonth->copy()->format('H:i'), // 0:00
                    'work_out' => $startOfMonth->copy()->addHours(8)->format('H:i'), // 8:00
                    'break_in' => [
                        0 => null,
                        $breakTime->id => $startOfMonth->copy()->addHours(9)->format('H:i'), // 9:00
                    ],
                    'break_out' => [
                        0 => null,
                        $breakTime->id => $startOfMonth->copy()->addHours(10)->format('H:i'), // 10:00
                    ],
                    'note' => '備考',
                ]);

        //「休憩時間が不適切な値です」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'break_in.' . $breakTime['id'] => '休憩時間が不適切な値です'
        ]);
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    #[Test]
    public function isBreakOffAfterClockOut()
    {
        $user = User::factory()->create();
        $startOfMonth = now()->startOfMonth();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $startOfMonth->copy()->format('Y-m-d'),
            'clock_in' => $startOfMonth->copy()->format('Y-m-d H:i:s'), // 0:00
            'clock_out' => $startOfMonth->copy()->addHours(8)->format('Y-m-d H:i:s'), // 8:00
        ]);
        $breakTime = BreakTime::factory()->create([
                'attendance_id' => $attendance->id,
                'clock_in' => $startOfMonth->copy()->addHours(6)->format('Y-m-d H:i:s'), // 6:00
                'clock_out' => $startOfMonth->copy()->addHours(7)->format('Y-m-d H:i:s'), // 7:00
        ]);

        // 1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        $this->actingAs($admin);

        // 2. 勤怠詳細ページを開く
        $response =  $this->get(route('admin.show', ['id' => $attendance->id]));
        $response->assertStatus(200);

        // 3. 休憩終了時間を退勤時間より後に設定する
        // 4. 保存処理をする
        $response =  $this->post(route('admin.update'), [
                            'attendance_id' => $attendance->id,
                            'user_id' => $user->id,
                            'current_attendance_status' => null,
                            'year' => $startOfMonth->copy()->format('Y'),
                            'month' => $startOfMonth->copy()->format('m'),
                            'day' => $startOfMonth->copy()->format('d'),
                            'work_in' => $startOfMonth->copy()->format('H:i'), // 0:00
                            'work_out' => $startOfMonth->copy()->addHours(8)->format('H:i'), // 8:00
                            'break_in' => [
                                0 => null,
                                $breakTime->id => $startOfMonth->copy()->addHours(6)->format('H:i'), // 6:00
                            ],
                            'break_out' => [
                                0 => null,
                                $breakTime->id => $startOfMonth->copy()->addHours(10)->format('H:i'), // 10:00
                            ],
                            'note' => '備考',
                        ]);

        //「休憩時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
                    'break_in.' . $breakTime['id'] => '休憩時間もしくは退勤時間が不適切な値です'
                ]);
    }

    /**
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    #[Test]
    public function NoteIsRequired()
    {
        $user = User::factory()->create();
        $now = now();

        $attendance = Attendance::factory()->create([
                            'user_id' => $user->id,
                            'work_date' => $now->copy()->format('Y-m-d'),
                            'clock_in' => $now->copy()->format('Y-m-d H:i:s'),
                            'clock_out' => $now->copy()->format('Y-m-d H:i:s'),
                        ]);

        // 1. 管理者ユーザーにログインをする
        $admin = User::factory()->create(['role' => Role::ADMIN]);
        session([
            'user_id' => $admin->id,
            'login_form' => LoginForm::ADMIN->value,
        ]);
        $this->actingAs($admin);

        // 2. 勤怠詳細ページを開く
        $response =  $this->get(route('admin.show', ['id' => $attendance->id]));
        $response->assertStatus(200);

        // 3. 備考欄を未入力のまま保存処理をする
        $response =  $this->post(route('admin.update'), [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'current_attendance_status' => null,
            'year' => $now->copy()->format('Y'),
            'month' => $now->copy()->format('m'),
            'day' => $now->copy()->format('d'),
            'work_in' => $now->copy()->format('H:i'),
            'work_out' => $now->copy()->format('H:i'),
            'break_in' => [
                0 => null,
            ],
            'break_out' => [
                0 => null,
            ],
            'note' => '',
        ]);

        // 「備考を記入してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'note' => '備考を記入してください'
        ]);
    }
}
