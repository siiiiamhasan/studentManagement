<?php
class StudentManager
{
    private $jsonFile = 'students.json';

    public function getAllStudents()
    {
        if (!file_exists($this->jsonFile)) {
            return [];
        }

        $data = file_get_contents($this->jsonFile);
        $students = json_decode($data, true);

        return is_array($students) ? $students : [];
    }


    public function getStudentById($id)
    {
        $students = $this->getAllStudents();

        foreach ($students as $student) {
            if ($student['id'] == $id) {
                return $student;
            }
        }

        return null;
    }


    public function create($data)
    {
        // Validate input
        $validation = $this->validateStudentData($data);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }

        $students = $this->getAllStudents();

        // Generate unique ID
        $id = $this->generateUniqueId($students);

        // Add new student
        $newStudent = [
            'id' => $id,
            'name' => sanitize($data['name']),
            'email' => strtolower(sanitize($data['email'])),
            'phone' => sanitize($data['phone']),
            'status' => $data['status']
        ];

        $students[] = $newStudent;

        // Save to JSON file
        if ($this->saveStudents($students)) {
            return ['success' => true, 'message' => 'Student created successfully', 'id' => $id];
        }

        return ['success' => false, 'message' => 'Failed to save student data'];
    }


    public function update($id, $data)
    {
        // Validate input
        $validation = $this->validateStudentData($data, false);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }

        $students = $this->getAllStudents();
        $found = false;

        // Find and update the student
        foreach ($students as &$student) {
            if ($student['id'] == $id) {
                $student['name'] = sanitize($data['name']);
                $student['email'] = strtolower(sanitize($data['email']));
                $student['phone'] = sanitize($data['phone']);
                $student['status'] = $data['status'];
                $found = true;
                break;
            }
        }

        if (!$found) {
            return ['success' => false, 'message' => 'Student not found'];
        }

        // Save to JSON file
        if ($this->saveStudents($students)) {
            return ['success' => true, 'message' => 'Student updated successfully'];
        }

        return ['success' => false, 'message' => 'Failed to update student data'];
    }

    public function delete($id)
    {
        $students = $this->getAllStudents();
        $initialCount = count($students);

        // Filter out the student with the given ID
        $students = array_filter($students, function ($student) use ($id) {
            return $student['id'] != $id;
        });

        // Re-index array
        $students = array_values($students);

        // Check if student was deleted
        if (count($students) === $initialCount) {
            return ['success' => false, 'message' => 'Student not found'];
        }

        // Save to JSON file
        if ($this->saveStudents($students)) {
            return ['success' => true, 'message' => 'Student deleted successfully'];
        }

        return ['success' => false, 'message' => 'Failed to delete student'];
    }

    private function validateStudentData($data, $checkDuplicate = true)
    {
        // Check required fields
        if (empty($data['name'])) {
            return ['valid' => false, 'message' => 'Name is required'];
        }

        if (empty($data['email'])) {
            return ['valid' => false, 'message' => 'Email is required'];
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Invalid email format'];
        }

        if (empty($data['phone'])) {
            return ['valid' => false, 'message' => 'Phone number is required'];
        }

        if (empty($data['status']) || !in_array($data['status'], ['Active', 'On Leave', 'Graduated', 'Inactive'])) {
            return ['valid' => false, 'message' => 'Invalid status'];
        }

        // Check for duplicate email 
        if ($checkDuplicate) {
            $students = $this->getAllStudents();
            foreach ($students as $student) {
                if (strtolower($student['email']) === strtolower($data['email'])) {
                    return ['valid' => false, 'message' => 'Email already exists'];
                }
            }
        }

        return ['valid' => true, 'message' => 'Valid'];
    }

    private function generateUniqueId($students)
    {
        $maxId = 0;

        foreach ($students as $student) {
            $id = (int)$student['id'];
            if ($id > $maxId) {
                $maxId = $id;
            }
        }

        return (string)($maxId + 1);
    }


    private function saveStudents($students)
    {
        $json = json_encode($students, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            return false;
        }

        return file_put_contents($this->jsonFile, $json) !== false;
    }
}

function sanitize($data)
{
    return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}
