<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;

function fileExtension($filePath) {
    $lastDotPos = strrpos($filePath, '.'); // Ищет с конца
    if($lastDotPos === false) { // Проверять нужно по типу - этот false не приведенный из другого типа 
        return '';
    }
    return substr($filePath, $lastDotPos + 1);
}

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\notebook>
 */
class notebookFactory extends Factory
{
    protected $filePaths;
    protected $acceptableFormats;

    function __construct() {
        parent::__construct();
        $this->acceptableFormats = [
            'png' => 'image/png',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
        ];
        $this->filePaths = array_filter(Storage::disk('factoryImages')->files(), function ($item) {
            return array_key_exists(fileExtension($item), $this->acceptableFormats);
        });
    }
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $pattern = [
            'full_name' => fake()->name(),
            'company' => fake()->company(),
            'phone' => fake()->e164PhoneNumber(),
            'email' => fake()->email(),
            'date_of_birth' => fake()->date('d-m-Y'),
            'photo_available' => fake()->boolean(),
        ];
        if ($pattern['photo_available']) {
            $randomPhotoPath = Arr::random($this->filePaths);
            $pattern['photo_type'] = $this->acceptableFormats[fileExtension($randomPhotoPath)];
            $pattern['photo_content'] = Storage::disk('factoryImages')->get($randomPhotoPath);
        }
        return $pattern;
    }
}
