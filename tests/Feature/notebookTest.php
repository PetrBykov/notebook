<?php

namespace Tests\Feature;

use App\Models\Notebook;
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class notebookTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_example()
    {
        // Проверка корректности ответов на запрос на получение фото.
        // Проверяется, что мы получаем корректный ответ на запросы на получение фото у всех, кого должны получить фото, и у тех, у кого не должны получить.
        // 1) Суммируются все, у кого фото есть, и все, кого фото нет
        // 2) Делается запросы на получение фото от каждой записи. Параллельно ведется счетчик успехов получения фото и неудач (не найдено)
        // 3) Если количество успехов совпадает с количеством тех, у кого есть фото, и количество неудач с количеством тех, у кого нет фото, то тест пройден.
        $countPhotoAvailable = Notebook::where('photo_available', true)->count();
        $countPhotoNotAvailable = Notebook::where('photo_available', false)->count();
        $allNotebookRequest = Notebook::select('id')->get();
        $IDs = array();
        $allNotebookRequest->each(function ($item, $key) use (&$IDs) {
            array_push($IDs, $item->id);
        });
        $countGetPhotoSuccess = 0;
        $countGetPhotoError = 0;
        foreach ($IDs as $id) {
            $response = $this->get(route('notebook.showPhoto', ['id' => $id]));
            $responseStatusCode = $response->getStatusCode();
            if ($responseStatusCode === 200) {
                $countGetPhotoSuccess++;
            } elseif ($responseStatusCode === 404) {
                $countGetPhotoError++;
            } else {
                throw new \Exception("Ответ пришел с ошибкой ( код ответа: $responseStatusCode ), скорее всего перегруз запросами. Нужно увеличить задержку между запросами");
            }
            sleep(2); // Задержка, чтобы не было Too many requests или сервер не упал
        }
        $this->assertEquals($countPhotoAvailable, $countGetPhotoSuccess);
        $this->assertEquals($countPhotoNotAvailable, $countGetPhotoError);
        // ------------------------------------------------------------------------------------
        // Проверка корректности отправляемых данных по API
        // 1) Делаем новую запись со всех полями (обязательными и необязательными)
        // 2) Извлекаем данные
        // 3) Проверяем, что данные сходятся
        
        // Первый пункт:
        $recordToSend = [
            'fullName' => fake()->name(),
            'company' => fake()->company(),
            'phone' => '+71234567890',
            'email' => fake()->email(),
            'dateOfBirth' => fake()->date(),
            'photoAvailable' => 1,
            'photoType' => 'image/png',
            'photoContent' => base64_encode(Storage::disk('factoryImages')->get('1.png')),
        ];
        $response = $this->postJson(route('notebook.store'), $recordToSend);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id', 'message',
        ]);
        $responseJSON = json_decode($response->getContent(), true);
        $newRecordID = $responseJSON['id'];
        // Второй пункт:
        $response = $this->get(route('notebook.show', ['id' => $newRecordID]));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id', 'fullName', 'company', 'phone', 'email', 'dateOfBirth', 'photoAvailable',
        ]);
        $responseJSON = json_decode($response->getContent(), true);
        unset($responseJSON['id']);
        unset($recordToSend['photoType']);
        unset($recordToSend['photoContent']);
        // Третий пункт
        $this->assertEquals($recordToSend, $responseJSON);
        $response = $this->get(route('notebook.showPhoto', ['id' => $newRecordID]));
        $this->assertEquals(Storage::disk('factoryImages')->get('1.png'), $response->getContent());
        // ------------------------------------------------------------------------------------
        // Проверка работы валидации новых записей
        // 1) Отправляем все возможные варианты, когда одно из обязательных полей отсутствует
        // 2) Отправляем запись, у которой поле "Есть фото" (photoAvailable) равно false
        //    и не указаны остальные параметры, связанные с фото (photoType, photoContent).
        //    Такая запись должна считатся валидированной
        // 3) Отправляем запись, котороый поле "Есть фото" (photoAvailable) равно true
        //    и не указаны остальные параметры, связанные с фото (photoType, photoContent).
        //    Такая запись НЕ должна считаться валидированной

        // Первый пункт
        // Нет ФИО
        $recordToSend = [
            'company' => fake()->company(),
            'phone' => fake()->e164PhoneNumber(),
            'email' => fake()->email(),
            'dateOfBirth' => fake()->date('d-m-Y'),
            'photoAvailable' => 1,
            'photoType' => 'image/png',
            'photoContent' => base64_encode(Storage::disk('factoryImages')->get('1.png')),
        ];
        // Чтобы валидатор laravel вернул ответ для API (JSON), а не для одностраничного сайта (редирект на предыдущую страницу),
        // должен быть обязательно заголовок "Accept: application/json"
        $response = $this->withHeaders(["Accept" => "application/json"])->postJson(route('notebook.store'), $recordToSend);
        $response->assertStatus(422);

        // Нет телефона
        $recordToSend = [
            'fullName' => fake()->name(),
            'company' => fake()->company(),
            'email' => fake()->email(),
            'dateOfBirth' => fake()->date('d-m-Y'),
            'photoAvailable' => 1,
            'photoType' => 'image/png',
            'photoContent' => base64_encode(Storage::disk('factoryImages')->get('1.png')),
        ];
        $response = $this->withHeaders(["Accept" => "application/json"])->postJson(route('notebook.store'), $recordToSend);
        $response->assertStatus(422);

        // Нет почты
        $recordToSend = [
            'fullName' => fake()->name(),
            'company' => fake()->company(),
            'phone' => fake()->e164PhoneNumber(),
            'dateOfBirth' => fake()->date('d-m-Y'),
            'photoAvailable' => 1,
            'photoType' => 'image/png',
            'photoContent' => base64_encode(Storage::disk('factoryImages')->get('1.png')),
        ];
        $response = $this->withHeaders(["Accept" => "application/json"])->postJson(route('notebook.store'), $recordToSend);
        $response->assertStatus(422);

        // Нет поля "Есть фото"
        $recordToSend = [
            'fullName' => fake()->name(),
            'company' => fake()->company(),
            'phone' => fake()->e164PhoneNumber(),
            'email' => fake()->email(),
            'dateOfBirth' => fake()->date('d-m-Y'),
            'photoType' => 'image/png',
            'photoContent' => base64_encode(Storage::disk('factoryImages')->get('1.png')),
        ];
        $response = $this->withHeaders(["Accept" => "application/json"])->postJson(route('notebook.store'), $recordToSend);
        $response->assertStatus(422);

        // Третий пункт
        $recordToSend = [
            'fullName' => fake()->name(),
            'company' => fake()->company(),
            'phone' => fake()->e164PhoneNumber(),
            'email' => fake()->email(),
            'dateOfBirth' => fake()->date('d-m-Y'),
            'photoAvailable' => 0,
            'photoType' => 'image/png',
            'photoContent' => base64_encode(Storage::disk('factoryImages')->get('1.png')),
        ];
        $response = $this->withHeaders(["Accept" => "application/json"])->postJson(route('notebook.store'), $recordToSend);
        $response->assertStatus(200);

        // Четвертый пункт
        $recordToSend = [
            'fullName' => fake()->name(),
            'company' => fake()->company(),
            'phone' => fake()->e164PhoneNumber(),
            'email' => fake()->email(),
            'dateOfBirth' => fake()->date('d-m-Y'),
            'photoAvailable' => 1,
        ];
        $response = $this->withHeaders(["Accept" => "application/json"])->postJson(route('notebook.store'), $recordToSend);
        $response->assertStatus(422);
    }
}
