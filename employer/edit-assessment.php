<?php
require_once '../config/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/assessment_functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employer') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$assessment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$success_message = '';
$error_message = '';

// Verify assessment belongs to this employer
$sql = "SELECT a.*, j.title as job_title 
        FROM assessments a
        JOIN jobs j ON a.job_id = j.id
        WHERE a.id = ? AND a.employer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $assessment_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage-jobs.php");
    exit();
}

$assessment = $result->fetch_assoc();
$questions = getAssessmentQuestions($assessment_id);

// Process add question form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_question'])) {
    $question_text = trim($_POST['question']);
    $question_type = $_POST['question_type'];
    $points = intval($_POST['points']);
    $order = count($questions) + 1;
    
    // Process options and correct answers based on question type
    $options = null;
    $correct_answer = null;
    
    if ($question_type == 'multiple_choice') {
        $options = $_POST['options'] ?? [];
        $correct_answer = $_POST['correct_option'] ?? '';
    } elseif ($question_type == 'checkbox') {
        $options = $_POST['options'] ?? [];
        $correct_answer = isset($_POST['correct_options']) ? $_POST['correct_options'] : [];
    } elseif ($question_type == 'boolean') {
        $options = ["True", "False"];
        $correct_answer = $_POST['correct_boolean'] ?? '';
    } elseif ($question_type == 'text') {
        $correct_answer = $_POST['text_answer'] ?? '';
    }
    
    if (empty($question_text)) {
        $error_message = "Question text is required.";
    } elseif (($question_type == 'multiple_choice' || $question_type == 'checkbox') && count($options) < 2) {
        $error_message = "At least two options are required for multiple choice or checkbox questions.";
    } else {
        // Add question
        $question_id = addAssessmentQuestion(
            $assessment_id,
            $question_text,
            $question_type,
            $options,
            $correct_answer,
            $points,
            $order
        );
        
        if ($question_id) {
            $success_message = "Question added successfully.";
            // Refresh questions list
            $questions = getAssessmentQuestions($assessment_id);
        } else {
            $error_message = "Error adding question.";
        }
    }
}

// Process update assessment status
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    
    if (count($questions) == 0 && $new_status == 'active') {
        $error_message = "You must add at least one question before activating the assessment.";
    } else {
        if (updateAssessmentStatus($assessment_id, $user_id, $new_status)) {
            $success_message = "Assessment status updated to " . ucfirst($new_status) . ".";
            // Refresh assessment data
            $stmt->execute();
            $result = $stmt->get_result();
            $assessment = $result->fetch_assoc();
        } else {
            $error_message = "Error updating assessment status.";
        }
    }
}

$base_path = '../';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar navigation -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Navigation</h5>
                </div>
                <div class="list-group list-group-flush">
    <a href="index.php" class="list-group-item list-group-item-action <?php echo $active_page == 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
    <a href="company-profile.php" class="list-group-item list-group-item-action <?php echo $active_page == 'profile' ? 'active' : ''; ?>">Company Profile</a>
    <a href="post-job.php" class="list-group-item list-group-item-action <?php echo $active_page == 'post' ? 'active' : ''; ?>">Post a Job</a>
    <a href="manage-jobs.php" class="list-group-item list-group-item-action <?php echo $active_page == 'jobs' ? 'active' : ''; ?>">Manage Jobs</a>
    <a href="manage-applications.php" class="list-group-item list-group-item-action <?php echo $active_page == 'applications' ? 'active' : ''; ?>">Applications</a>
    <a href="interviews.php" class="list-group-item list-group-item-action <?php echo $active_page == 'interviews' ? 'active' : ''; ?>">Interviews</a>
    <a href="../messages/index.php" class="list-group-item list-group-item-action <?php echo $active_page == 'messages' ? 'active' : ''; ?>">Messages</a>
    <a href="../notifications/index.php" class="list-group-item list-group-item-action <?php echo $active_page == 'notifications' ? 'active' : ''; ?>">Notifications</a>
</div>
            </div>
        </div>
        
        <!-- Main content -->
        <div class="col-md-9">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0"><?php echo htmlspecialchars($assessment['title']); ?></h4>
                        <div class="text-muted small">For job: <?php echo htmlspecialchars($assessment['job_title']); ?></div>
                    </div>
                    <div>
                        <span class="badge bg-<?php echo $assessment['status'] === 'active' ? 'success' : ($assessment['status'] === 'draft' ? 'warning' : 'secondary'); ?>">
                            <?php echo ucfirst($assessment['status']); ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h5>Assessment Details</h5>
                        <div class="row">
                            <div class="col-md-8">
                                <p><?php echo nl2br(htmlspecialchars($assessment['description'])); ?></p>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <p class="mb-1"><strong>Questions:</strong> <?php echo count($questions); ?></p>
                                        <p class="mb-1"><strong>Time Limit:</strong> <?php echo $assessment['time_limit'] > 0 ? $assessment['time_limit'] . ' minutes' : 'No limit'; ?></p>
                                        <p class="mb-1"><strong>Passing Score:</strong> <?php echo $assessment['passing_score']; ?>%</p>
                                        <p class="mb-0"><strong>Required:</strong> <?php echo $assessment['is_required'] ? 'Yes' : 'No'; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <form method="post" action="">
                            <div class="input-group">
                                <select name="status" class="form-select">
                                    <option value="draft" <?php echo $assessment['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="active" <?php echo $assessment['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $assessment['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                            </div>
                        </form>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-4">
                        <h5>Questions</h5>
                        
                        <?php if (count($questions) > 0): ?>
                            <div class="accordion" id="questionsAccordion">
                                <?php foreach ($questions as $index => $question): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading<?php echo $question['id']; ?>">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $question['id']; ?>" aria-expanded="false" aria-controls="collapse<?php echo $question['id']; ?>">
                                                <div class="d-flex w-100 justify-content-between align-items-center">
                                                    <div class="me-3">
                                                        <span class="badge bg-secondary me-2"><?php echo $index + 1; ?></span>
                                                        <?php echo htmlspecialchars($question['question']); ?>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="badge bg-info"><?php echo ucfirst($question['question_type']); ?></span>
                                                        <span class="badge bg-primary"><?php echo $question['points']; ?> pts</span>
                                                    </div>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="collapse<?php echo $question['id']; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $question['id']; ?>" data-bs-parent="#questionsAccordion">
                                            <div class="accordion-body">
                                                <?php if ($question['question_type'] === 'multiple_choice' || $question['question_type'] === 'checkbox'): ?>
                                                    <div class="mb-3">
                                                        <p><strong>Options:</strong></p>
                                                        <ul class="list-group">
                                                            <?php foreach ($question['options_array'] as $option): ?>
                                                                <li class="list-group-item <?php 
                                                                    if ($question['question_type'] === 'multiple_choice' && $option === $question['correct_answer']) {
                                                                        echo 'list-group-item-success';
                                                                    } elseif ($question['question_type'] === 'checkbox' && is_array($question['correct_answer_array']) && in_array($option, $question['correct_answer_array'])) {
                                                                        echo 'list-group-item-success';
                                                                    }
                                                                ?>">
                                                                    <?php echo htmlspecialchars($option); ?>
                                                                    <?php 
                                                                    if ($question['question_type'] === 'multiple_choice' && $option === $question['correct_answer']) {
                                                                        echo '<span class="float-end badge bg-success">Correct</span>';
                                                                    } elseif ($question['question_type'] === 'checkbox' && is_array($question['correct_answer_array']) && in_array($option, $question['correct_answer_array'])) {
                                                                        echo '<span class="float-end badge bg-success">Correct</span>';
                                                                    }
                                                                    ?>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </div>
                                                <?php elseif ($question['question_type'] === 'boolean'): ?>
                                                    <p><strong>Correct Answer:</strong> <?php echo $question['correct_answer']; ?></p>
                                                <?php elseif ($question['question_type'] === 'text'): ?>
                                                    <p><strong>Sample Answer:</strong> <?php echo htmlspecialchars($question['correct_answer'] ?? 'No sample answer provided'); ?></p>
                                                <?php endif; ?>
                                                
                                                <div class="d-flex justify-content-end mt-3">
                                                    <a href="edit-question.php?id=<?php echo $question['id']; ?>" class="btn btn-outline-primary btn-sm me-2">Edit</a>
                                                    <a href="delete-question.php?id=<?php echo $question['id']; ?>&assessment_id=<?php echo $assessment_id; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this question?')">Delete</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No questions added yet. Use the form below to add questions.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-4">
                        <h5>Add Question</h5>
                        <form method="post" action="" id="questionForm">
                            <div class="mb-3">
                                <label for="question" class="form-label">Question Text*</label>
                                <textarea class="form-control" id="question" name="question" rows="2" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="question_type" class="form-label">Question Type*</label>
                                <select class="form-select" id="question_type" name="question_type" required>
                                    <option value="multiple_choice">Multiple Choice (Single Answer)</option>
                                    <option value="checkbox">Multiple Choice (Multiple Answers)</option>
                                    <option value="boolean">True/False</option>
                                    <option value="text">Text Answer</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="points" class="form-label">Points</label>
                                <input type="number" class="form-control" id="points" name="points" min="1" max="100" value="1">
                            </div>
                            
                            <!-- Multiple Choice Options -->
                            <div id="multipleChoiceOptions" class="question-type-section">
                                <div class="mb-3">
                                    <label class="form-label">Options*</label>
                                    <div id="options-container" class="mb-2">
                                        <div class="input-group mb-2">
                                            <div class="input-group-text">
                                                <input class="form-check-input mt-0" type="radio" name="correct_option" value="Option 1" required>
                                            </div>
                                            <input type="text" class="form-control" name="options[]" placeholder="Option 1" value="Option 1" required>
                                            <button type="button" class="btn btn-outline-danger remove-option" disabled>Remove</button>
                                        </div>
                                        <div class="input-group mb-2">
                                            <div class="input-group-text">
                                                <input class="form-check-input mt-0" type="radio" name="correct_option" value="Option 2">
                                            </div>
                                            <input type="text" class="form-control" name="options[]" placeholder="Option 2" value="Option 2" required>
                                            <button type="button" class="btn btn-outline-danger remove-option" disabled>Remove</button>
                                        </div>
                                    </div>
                                    <button type="button" id="add-option" class="btn btn-outline-primary btn-sm">+ Add Option</button>
                                    <div class="form-text">Select the radio button for the correct answer.</div>
                                </div>
                            </div>
                            
                            <!-- Checkbox Options -->
                            <div id="checkboxOptions" class="question-type-section" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label">Options* (Check all correct answers)</label>
                                    <div id="checkbox-options-container" class="mb-2">
                                        <div class="input-group mb-2">
                                            <div class="input-group-text">
                                                <input class="form-check-input mt-0" type="checkbox" name="correct_options[]" value="Option 1">
                                            </div>
                                            <input type="text" class="form-control" name="options[]" placeholder="Option 1" value="Option 1" required>
                                            <button type="button" class="btn btn-outline-danger remove-checkbox-option" disabled>Remove</button>
                                        </div>
                                        <div class="input-group mb-2">
                                            <div class="input-group-text">
                                                <input class="form-check-input mt-0" type="checkbox" name="correct_options[]" value="Option 2">
                                            </div>
                                            <input type="text" class="form-control" name="options[]" placeholder="Option 2" value="Option 2" required>
                                            <button type="button" class="btn btn-outline-danger remove-checkbox-option" disabled>Remove</button>
                                        </div>
                                    </div>
                                    <button type="button" id="add-checkbox-option" class="btn btn-outline-primary btn-sm">+ Add Option</button>
                                    <div class="form-text">Check the boxes for all correct answers.</div>
                                </div>
                            </div>
                            
                            <!-- Boolean Options -->
                            <div id="booleanOptions" class="question-type-section" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label">Correct Answer*</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="correct_boolean" id="boolean-true" value="True" checked>
                                        <label class="form-check-label" for="boolean-true">True</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="correct_boolean" id="boolean-false" value="False">
                                        <label class="form-check-label" for="boolean-false">False</label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Text Answer -->
                            <div id="textAnswerOptions" class="question-type-section" style="display: none;">
                                <div class="mb-3">
                                    <label for="text_answer" class="form-label">Sample Correct Answer (Optional)</label>
                                    <input type="text" class="form-control" id="text_answer" name="text_answer">
                                    <div class="form-text">This will be used to automatically grade text answers. Leave blank if manual grading is preferred.</div>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="add_question" class="btn btn-primary">Add Question</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="manage-jobs.php" class="btn btn-secondary">Back to Jobs</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Question type change handling
    const questionTypeSelect = document.getElementById('question_type');
    const questionTypes = ['multipleChoiceOptions', 'checkboxOptions', 'booleanOptions', 'textAnswerOptions'];
    
    questionTypeSelect.addEventListener('change', function() {
        // Hide all sections
        questionTypes.forEach(type => {
            document.getElementById(type).style.display = 'none';
        });
        
        // Show relevant section
        switch(this.value) {
            case 'multiple_choice':
                document.getElementById('multipleChoiceOptions').style.display = 'block';
                break;
            case 'checkbox':
                document.getElementById('checkboxOptions').style.display = 'block';
                break;
            case 'boolean':
                document.getElementById('booleanOptions').style.display = 'block';
                break;
            case 'text':
                document.getElementById('textAnswerOptions').style.display = 'block';
                break;
        }
    });
    
    // Add option for multiple choice
    document.getElementById('add-option').addEventListener('click', function() {
        const container = document.getElementById('options-container');
        const optionCount = container.children.length + 1;
        const optionValue = `Option ${optionCount}`;
        
        const newOption = document.createElement('div');
        newOption.className = 'input-group mb-2';
        newOption.innerHTML = `
            <div class="input-group-text">
                <input class="form-check-input mt-0" type="radio" name="correct_option" value="${optionValue}">
            </div>
            <input type="text" class="form-control" name="options[]" placeholder="${optionValue}" value="${optionValue}" required>
            <button type="button" class="btn btn-outline-danger remove-option">Remove</button>
        `;
        
        container.appendChild(newOption);
        
        // Update radio values when option text changes
        const newInput = newOption.querySelector('input[type="text"]');
        const newRadio = newOption.querySelector('input[type="radio"]');
        
        newInput.addEventListener('input', function() {
            newRadio.value = this.value;
        });
        
        // Enable remove buttons if we have more than 2 options
        updateRemoveButtonStatus();
        
        // Add remove option functionality
        newOption.querySelector('.remove-option').addEventListener('click', function() {
            container.removeChild(newOption);
            updateRemoveButtonStatus();
        });
    });
    
    // Add option for checkbox
    document.getElementById('add-checkbox-option').addEventListener('click', function() {
        const container = document.getElementById('checkbox-options-container');
        const optionCount = container.children.length + 1;
        const optionValue = `Option ${optionCount}`;
        
        const newOption = document.createElement('div');
        newOption.className = 'input-group mb-2';
        newOption.innerHTML = `
            <div class="input-group-text">
                <input class="form-check-input mt-0" type="checkbox" name="correct_options[]" value="${optionValue}">
            </div>
            <input type="text" class="form-control" name="options[]" placeholder="${optionValue}" value="${optionValue}" required>
            <button type="button" class="btn btn-outline-danger remove-checkbox-option">Remove</button>
        `;
        
        container.appendChild(newOption);
        
        // Update checkbox values when option text changes
        const newInput = newOption.querySelector('input[type="text"]');
        const newCheckbox = newOption.querySelector('input[type="checkbox"]');
        
        newInput.addEventListener('input', function() {
            newCheckbox.value = this.value;
        });
        
        // Enable remove buttons if we have more than 2 options
        updateCheckboxRemoveButtonStatus();
        
        // Add remove option functionality
        newOption.querySelector('.remove-checkbox-option').addEventListener('click', function() {
            container.removeChild(newOption);
            updateCheckboxRemoveButtonStatus();
        });
    });
    
    // Add input change listeners for existing options
    document.querySelectorAll('#options-container input[type="text"]').forEach(function(input) {
        const radio = input.parentNode.querySelector('input[type="radio"]');
        input.addEventListener('input', function() {
            radio.value = this.value;
        });
    });
    
    document.querySelectorAll('#checkbox-options-container input[type="text"]').forEach(function(input) {
        const checkbox = input.parentNode.querySelector('input[type="checkbox"]');
        input.addEventListener('input', function() {
            checkbox.value = this.value;
        });
    });
    
    // Helper function to update remove button status
    function updateRemoveButtonStatus() {
        const container = document.getElementById('options-container');
        const removeButtons = container.querySelectorAll('.remove-option');
        
        if (removeButtons.length <= 2) {
            removeButtons.forEach(button => button.disabled = true);
        } else {
            removeButtons.forEach(button => button.disabled = false);
        }
    }
    
    // Helper function to update checkbox remove button status
    function updateCheckboxRemoveButtonStatus() {
        const container = document.getElementById('checkbox-options-container');
        const removeButtons = container.querySelectorAll('.remove-checkbox-option');
        
        if (removeButtons.length <= 2) {
            removeButtons.forEach(button => button.disabled = true);
        } else {
            removeButtons.forEach(button => button.disabled = false);
        }
    }
    
    // Initialize button status
    updateRemoveButtonStatus();
    updateCheckboxRemoveButtonStatus();
});
</script>

<?php include '../includes/footer.php'; ?>