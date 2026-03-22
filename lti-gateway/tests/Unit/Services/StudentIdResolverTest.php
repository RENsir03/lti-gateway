<?php

namespace Tests\Unit\Services;

use App\Exceptions\MissingStudentIdException;
use App\Services\StudentIdResolver;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class StudentIdResolverTest extends TestCase
{
    private StudentIdResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new StudentIdResolver();
    }

    public function test_extract_from_custom_student_id(): void
    {
        $request = new Request(['custom_student_id' => '2024001']);
        
        $result = $this->resolver->extract($request);
        
        $this->assertEquals('2024001', $result);
    }

    public function test_extract_from_lis_person_sourcedid(): void
    {
        $request = new Request(['lis_person_sourcedid' => 'STU2024001']);
        
        $result = $this->resolver->extract($request);
        
        $this->assertEquals('STU2024001', $result);
    }

    public function test_custom_student_id_priority_over_sourcedid(): void
    {
        $request = new Request([
            'custom_student_id' => '2024001',
            'lis_person_sourcedid' => 'STU2024001',
        ]);
        
        $result = $this->resolver->extract($request);
        
        $this->assertEquals('2024001', $result);
    }

    public function test_throws_exception_when_no_student_id(): void
    {
        $request = new Request([]);
        
        $this->expectException(MissingStudentIdException::class);
        
        $this->resolver->extract($request);
    }

    public function test_sanitizes_student_id(): void
    {
        $request = new Request(['custom_student_id' => '  2024-001_abc  ']);
        
        $result = $this->resolver->extract($request);
        
        $this->assertEquals('2024-001_ABC', $result);
    }

    public function test_validates_student_id_length(): void
    {
        $this->assertFalse($this->resolver->validate('123')); // 太短
        $this->assertFalse($this->resolver->validate(str_repeat('A', 21))); // 太长
        $this->assertTrue($this->resolver->validate('2024001')); // 正常
    }
}
