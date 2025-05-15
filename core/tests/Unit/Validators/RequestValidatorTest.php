<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use App\Validations\RequestValidator;
use App\Exceptions\General\InvalidRequestException;

class RequestValidatorTest extends TestCase
{
    public function makeValidator()
    {
        return new RequestValidator;
    }

    public function test_validate_validData_void()
    {
        $request = new Request([
            'name' => 'name',
            'age' => 14
        ]);

        $rules = [
            'name' => 'required',
            'age' => 'required|integer'
        ];

        $validator = $this->makeValidator();
        $response = $validator->validate($request, $rules);

        $this->assertNull($response);
    }

    public function test_validate_invalidData_invalidRequestException()
    {
        $this->expectException(InvalidRequestException::class);

        $request = new Request([
            'name' => 'name',
        ]);

        $rules = [
            'name' => 'required',
            'age' => 'required|integer'
        ];

        $validator = $this->makeValidator();
        $validator->validate($request, $rules);
    }
}
