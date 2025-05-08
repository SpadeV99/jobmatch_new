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
        WHERE a.id = ? AND j.employer_id = ?";
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

// Process question deletion
if (isset($_POST['delete_question']) && isset($_POST['question_id'])) {
    $question_id = intval($_POST['question_id']);
    
    $sql = "DELETE FROM assessment_questions WHERE id = ? AND assessment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $question_id, $assessment_id);
    
    if ($stmt->execute()) {
        $success_message = "Question deleted successfully!";
        // Refresh questions list
        $questions = getAssessmentQuestions($assessment_id);
    } else {
        $error_message = "Failed to delete question.";
    }
}

// Process new question form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_question'])) {
    $question_text = trim($_POST['question']);
    $question_type = $_POST['question_type'];
    $points = intval($_POST['points']);
    $order = intval($_POST['order']);
    
    // Multiple choice/checkbox options
    $options = [];
    if (isset($_POST['options']) && is_array($_POST['options'])) {
        foreach ($_POST['options'] as $option) {
            if (trim($option) !== '') {
                $options[] = trim($option);
            }
        }
    }
    
    // Correct answer(s)
    $correct_answer = null;
    
    if ($question_type === 'multiple_choice' && isset($_POST['correct_option'])) {
        $correct_index = intval($_POST['correct_option']);
        $correct_answer = isset($options[$correct_index]) ? $options[$correct_index] : '';
    } elseif ($question_type === 'checkbox' && isset($_POST['correct_options']) && is_array($_POST['correct_options'])) {
        $correct_answers = [];
        foreach ($_POST['correct_options'] as $index) {
            if (isset($options[intval($index)])) {
                $correct_answers[] = $options[intval($index)];
            }
        }
        $correct_answer = json_encode($correct_answers);
    } elseif ($question_type === 'boolean') {
        $correct_answer = $_POST['correct_boolean'];
    } elseif ($question_type === 'text' && isset($_POST['correct_text'])) {
        $correct_answer = trim($_POST['correct_text']);
    }
    
    // Validate inputs
    $validation_errors = [];
    
    if (empty($question_text)) {
        $validation_errors[] = "Question text is required.";
    }
    
    if (($question_type === 'multiple_choice' || $question_type === 'checkbox') && count($options) < 2) {
        $validation_errors[] = "At least two options are required for multiple choice questions.";
    }
    
    if (empty($validation_errors)) {
        // Convert options to JSON
        $options_json = !empty($options) ? json_encode($options) : null;
        
        // Insert question
        $sql = "INSERT INTO assessment_questions 
                (assessment_id, question, question_type, options, correct_answer, points, `order`) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssii", $assessment_id, $question_text, $question_type, 
                        $options_json, $correct_answer, $points, $order);
        
        if ($stmt->execute()) {
            $success_message = "Question added successfully!";
            // Refresh questions list
            $questions = getAssessmentQuestions($assessment_id);
        } else {
            $error_message = "Failed to add question: " . $conn->error;
        }
    } else {
        $error_message = implode("<br>", $validation_errors);
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
                    <h5 class="mb-0">Employer Navigation</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="index.php" class="list-group-item list-group-item-action">Dashboard</a>
                    <a href="post-job.php" class="list-group-item list-group-item-action">Post a Job</a>
                    <a href="manage-jobs.php" class="list-group-item list-group-item-action active">Manage Jobs</a>
                    <a href="company-profile.php" class="list-group-item list-group-item-action">Company Profile</a>
                </div>
            </div>
        </div>
        
        <!-- Main content -->
        <div class="col-md-9">
            <!-- Assessment Info Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Assessment Questions</h4>
                    <div>
                        <a href="create-assessment.php?id=<?php echo $assessment_id; ?>" class="btn btn-outline-secondary btn-sm me-2">
                            <i class="bi bi-gear"></i> Assessment Settings
                        </a>
                        <a href="manage-jobs.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-arrow-left"></i> Back to Jobs
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <div><strong>Assessment:</strong> <?php echo htmlspecialchars($assessment['title']); ?></div>
                        <div><strong>For Job:</strong> <?php echo htmlspecialchars($assessment['job_title']); ?></div>
                        <div><strong>Status:</strong> 
                            <span class="badge bg-<?php echo $assessment['status'] === 'active' ? 'success' : ($assessment['status'] === 'draft' ? 'warning' : 'secondary'); ?>">
                                <?php echo ucfirst($assessment['status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Current Questions (<?php echo count($questions); ?>)</h5>
                        <?php if (empty($questions)): ?>
                            <div class="alert alert-warning">
                                No questions added yet. Use the form below to add questions.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Question</th>
                                            <th>Type</th>
                                            <th>Points</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($questions as $index => $question): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars(substr($question['question'], 0, 50)) . (strlen($question['question']) > 50 ? '...' : ''); ?></td>
                                                <td><?php echo ucwords(str_replace('_', ' ', $question['question_type'])); ?></td>
                                                <td><?php echo $question['points']; ?></td>
                                                <td>
                                                    <form method="post" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this question?')">
                                                        <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                                        <button type="submit" name="delete_question" class="btn btn-danger btn-sm">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <hr>
                    
                    <!-- Add Question Form -->
                    <div class="mt-4">
                        <h5>Add New Question</h5>
                        <form method="post" action="" id="question-form">
                            <div class="mb-3">
                                <label for="question" class="form-label">Question Text <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="question" name="question" rows="2" required></textarea>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="question_type" class="form-label">Question Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="question_type" name="question_type" required>
                                        <option value="multiple_choice">Multiple Choice</option>
                                        <option value="checkbox">Multiple Selection</option>
                                        <option value="boolean">True/False</option>
                                        <option value="text">Text Answer</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="points" class="form-label">Points</label>
                                    <input type="number" class="form-control" id="points" name="points" min="1" value="1">
                                </div>
                                <div class="col-md-4">
                                    <label for="order" class="form-label">Display Order</label>
                                    <input type="number" class="form-control" id="order" name="order" min="0" value="<?php echo count($questions); ?>">
                                </div>
                            </div>
                            
                            <!-- Multiple choice options -->
                            <div id="options-container" class="mb-3">
                                <label class="form-label">Options <span class="text-danger">*</span></label>
                                <div class="options-list">
                                    <div class="input-group mb-2">
                                        <div class="input-group-text">
                                            <input type="radio" name="correct_option" value="0" class="correct-option" checked>
                                        </div>
                                        <input type="text" class="form-control" name="options[]" placeholder="Option 1" required>
                                        <button type="button" class="btn btn-outline-danger remove-option" disabled>
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                    <div class="input-group mb-2">
                                        <div class="input-group-text">
                                            <input type="radio" name="correct_option" value="1" class="correct-option">
                                        </div>
                                        <input type="text" class="form-control" name="options[]" placeholder="Option 2" required>
                                        <button type="button" class="btn btn-outline-danger remove-option" disabled>
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                </div>
                                <button type="button" id="add-option" class="btn btn-outline-secondary btn-sm mt-2">
                                    <i class="bi bi-plus"></i> Add Option
                                </button>
                            </div>
                            
                            <!-- Multiple selection options -->
                            <div id="checkbox-container" class="mb-3" style="display:none">
                                <label class="form-label">Options (select all correct answers) <span class="text-danger">*</span></label>
                                <div class="options-list">
                                    <div class="input-group mb-2">
                                        <div class="input-group-text">
                                            <input type="checkbox" name="correct_options[]" value="0" class="correct-option-cb">
                                        </div>
                                        <input type="text" class="form-control" name="options_cb[]" placeholder="Option 1">
                                        <button type="button" class="btn btn-outline-danger remove-option-cb" disabled>
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                    <div class="input-group mb-2">
                                        <div class="input-group-text">
                                            <input type="checkbox" name="correct_options[]" value="1" class="correct-option-cb">
                                        </div>
                                        <input type="text" class="form-control" name="options_cb[]" placeholder="Option 2">
                                        <button type="button" class="btn btn-outline-danger remove-option-cb" disabled>
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                </div>
                                <button type="button" id="add-option-cb" class="btn btn-outline-secondary btn-sm mt-2">
                                    <i class="bi bi-plus"></i> Add Option
                                </button>
                            </div>
                            
                            <!-- Boolean option -->
                            <div id="boolean-container" class="mb-3" style="display:none">
                                <label class="form-label">Correct Answer <span class="text-danger">*</span></label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="correct_boolean" id="boolean-true" value="True" checked>
                                    <label class="form-check-label" for="boolean-true">
                                        True
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="correct_boolean" id="boolean-false" value="False">
                                    <label class="form-check-label" for="boolean-false">
                                        False
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Text answer -->
                            <div id="text-container" class="mb-3" style="display:none">
                                <label for="correct_text" class="form-label">Correct Answer (case insensitive) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="correct_text" name="correct_text" placeholder="Enter the correct answer">
                                <div class="form-text">For text questions, the candidate's answer will be compared case-insensitively.</div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="save_question" class="btn btn-primary">
                                    Add Question
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="create-assessment.php?id=<?php echo $assessment_id; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-gear"></i> Assessment Settings
                        </a>
                        <a href="assessment-results.php?assessment_id=<?php echo $assessment_id; ?>" class="btn btn-primary">
                            <i class="bi bi-list-check"></i> View Results
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Question type change handler
    const questionType = document.getElementById('question_type');
    const optionsContainer = document.getElementById('options-container');
    const checkboxContainer = document.getElementById('checkbox-container');
    const booleanContainer = document.getElementById('boolean-container');
    const textContainer = document.getElementById('text-container');
    
    questionType.addEventListener('change', function() {
        // Hide all containers
        optionsContainer.style.display = 'none';
        checkboxContainer.style.display = 'none';
        booleanContainer.style.display = 'none';
        textContainer.style.display = 'none';
        
        // Show container based on selected type
        if (this.value === 'multiple_choice') {
            optionsContainer.style.display = 'block';
        } else if (this.value === 'checkbox') {
            checkboxContainer.style.display = 'block';
        } else if (this.value === 'boolean') {
            booleanContainer.style.display = 'block';
        } else if (this.value === 'text') {
            textContainer.style.display = 'block';
        }
    });
    
    // Add option button handler
    document.getElementById('add-option').addEventListener('click', function() {
        const optionsList = document.querySelector('#options-container .options-list');
        const newOption = document.createElement('div');
        newOption.className = 'input-group mb-2';
        
        const numOptions = optionsList.children.length;
        
        newOption.innerHTML = `
            <div class="input-group-text">
                <input type="radio" name="correct_option" value="${numOptions}" class="correct-option">
            </div>
            <input type="text" class="form-control" name="options[]" placeholder="Option ${numOptions + 1}" required>
            <button type="button" class="btn btn-outline-danger remove-option">
                <i class="bi bi-x"></i>
            </button>
        `;
        
        optionsList.appendChild(newOption);
        
        // Enable remove buttons if there are more than 2 options
        toggleRemoveButtons();
    });
    
    // Add checkbox option button handler
    document.getElementById('add-option-cb').addEventListener('click', function() {
        const optionsList = document.querySelector('#checkbox-container .options-list');
        const newOption = document.createElement('div');
        newOption.className = 'input-group mb-2';
        
        const numOptions = optionsList.children.length;
        
        newOption.innerHTML = `
            <div class="input-group-text">
                <input type="checkbox" name="correct_options[]" value="${numOptions}" class="correct-option-cb">
            </div>
            <input type="text" class="form-control" name="options_cb[]" placeholder="Option ${numOptions + 1}" required>
            <button type="button" class="btn btn-outline-danger remove-option-cb">
                <i class="bi bi-x"></i>
            </button>
        `;
        
        optionsList.appendChild(newOption);
        
        // Enable remove buttons if there are more than 2 options
        toggleRemoveButtons();
    });
    
    // Remove option button handler (using event delegation)
    document.addEventListener('click', function(e) {
        if (e.target && e.target.closest('.remove-option')) {
            const button = e.target.closest('.remove-option');
            button.closest('.input-group').remove();
            
            // Update radio button values
            const radioButtons = document.querySelectorAll('#options-container .correct-option');
            radioButtons.forEach((radio, index) => {
                radio.value = index;
            });
            
            // Enable/disable remove buttons
            toggleRemoveButtons();
        }
        
        if (e.target && e.target.closest('.remove-option-cb')) {
            const button = e.target.closest('.remove-option-cb');
            button.closest('.input-group').remove();
            
            // Update checkbox values
            const checkboxes = document.querySelectorAll('#checkbox-container .correct-option-cb');
            checkboxes.forEach((cb, index) => {
                cb.value = index;
            });
            
            // Enable/disable remove buttons
            toggleRemoveButtons();
        }
    });
    
    // Helper function to enable/disable remove buttons
    function toggleRemoveButtons() {
        // For multiple choice
        const optionsList = document.querySelector('#options-container .options-list');
        const removeButtons = optionsList.querySelectorAll('.remove-option');
        
        if (optionsList.children.length > 2) {
            removeButtons.forEach(btn => btn.disabled = false);
        } else {
            removeButtons.forEach(btn => btn.disabled = true);
        }
        
        // For checkboxes
        const checkboxList = document.querySelector('#checkbox-container .options-list');
        const removeCheckboxButtons = checkboxList.querySelectorAll('.remove-option-cb');
        
        if (checkboxList.children.length > 2) {
            removeCheckboxButtons.forEach(btn => btn.disabled = false);
        } else {
            removeCheckboxButtons.forEach(btn => btn.disabled = true);
        }
    }
    
    // Form submission handler
    document.getElementById('question-form').addEventListener('submit', function(e) {
        const questionType = document.getElementById('question_type').value;
        
        // For multiple choice
        if (questionType === 'multiple_choice') {
            // Copy options to the main options array
            const options = document.querySelectorAll('#options-container .options-list input[type="text"]');
            
            if (options.length < 2) {
                alert('You need at least 2 options for multiple choice questions');
                e.preventDefault();
                return;
            }
            
            // Check if at least one option is selected as correct
            const correct = document.querySelector('#options-container .options-list input[type="radio"]:checked');
            if (!correct) {
                alert('You must select a correct answer');
                e.preventDefault();
                return;
            }
        }
        
        // For checkbox
        if (questionType === 'checkbox') {
            // Copy checkbox options to the main options array
            const options = document.querySelectorAll('#checkbox-container .options-list input[type="text"]');
            const mainOptions = document.querySelectorAll('input[name="options[]"]');
            
            // Clear main options
            for (let i = mainOptions.length - 1; i >= 0; i--) {
                mainOptions[i].parentNode.removeChild(mainOptions[i]);
            }
            
            // Add checkbox options to main options
            options.forEach((option, index) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'options[]';
                input.value = option.value;
                document.getElementById('question-form').appendChild(input);
            });
            
            if (options.length < 2) {
                alert('You need at least 2 options for multiple selection questions');
                e.preventDefault();
                return;
            }
            
            // Check// filepath: c:\laragon\www\jobmatch_new\employer\edit-assessment-questions.php
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
        WHERE a.id = ? AND j.employer_id = ?";
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

// Process question deletion
if (isset($_POST['delete_question']) && isset($_POST['question_id'])) {
    $question_id = intval($_POST['question_id']);
    
    $sql = "DELETE FROM assessment_questions WHERE id = ? AND assessment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $question_id, $assessment_id);
    
    if ($stmt->execute()) {
        $success_message = "Question deleted successfully!";
        // Refresh questions list
        $questions = getAssessmentQuestions($assessment_id);
    } else {
        $error_message = "Failed to delete question.";
    }
}

// Process new question form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_question'])) {
    $question_text = trim($_POST['question']);
    $question_type = $_POST['question_type'];
    $points = intval($_POST['points']);
    $order = intval($_POST['order']);
    
    // Multiple choice/checkbox options
    $options = [];
    if (isset($_POST['options']) && is_array($_POST['options'])) {
        foreach ($_POST['options'] as $option) {
            if (trim($option) !== '') {
                $options[] = trim($option);
            }
        }
    }
    
    // Correct answer(s)
    $correct_answer = null;
    
    if ($question_type === 'multiple_choice' && isset($_POST['correct_option'])) {
        $correct_index = intval($_POST['correct_option']);
        $correct_answer = isset($options[$correct_index]) ? $options[$correct_index] : '';
    } elseif ($question_type === 'checkbox' && isset($_POST['correct_options']) && is_array($_POST['correct_options'])) {
        $correct_answers = [];
        foreach ($_POST['correct_options'] as $index) {
            if (isset($options[intval($index)])) {
                $correct_answers[] = $options[intval($index)];
            }
        }
        $correct_answer = json_encode($correct_answers);
    } elseif ($question_type === 'boolean') {
        $correct_answer = $_POST['correct_boolean'];
    } elseif ($question_type === 'text' && isset($_POST['correct_text'])) {
        $correct_answer = trim($_POST['correct_text']);
    }
    
    // Validate inputs
    $validation_errors = [];
    
    if (empty($question_text)) {
        $validation_errors[] = "Question text is required.";
    }
    
    if (($question_type === 'multiple_choice' || $question_type === 'checkbox') && count($options) < 2) {
        $validation_errors[] = "At least two options are required for multiple choice questions.";
    }
    
    if (empty($validation_errors)) {
        // Convert options to JSON
        $options_json = !empty($options) ? json_encode($options) : null;
        
        // Insert question
        $sql = "INSERT INTO assessment_questions 
                (assessment_id, question, question_type, options, correct_answer, points, `order`) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssii", $assessment_id, $question_text, $question_type, 
                        $options_json, $correct_answer, $points, $order);
        
        if ($stmt->execute()) {
            $success_message = "Question added successfully!";
            // Refresh questions list
            $questions = getAssessmentQuestions($assessment_id);
        } else {
            $error_message = "Failed to add question: " . $conn->error;
        }
    } else {
        $error_message = implode("<br>", $validation_errors);
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
                    <h5 class="mb-0">Employer Navigation</h5>
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
            <!-- Assessment Info Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Assessment Questions</h4>
                    <div>
                        <a href="create-assessment.php?id=<?php echo $assessment_id; ?>" class="btn btn-outline-secondary btn-sm me-2">
                            <i class="bi bi-gear"></i> Assessment Settings
                        </a>
                        <a href="manage-jobs.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-arrow-left"></i> Back to Jobs
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <div><strong>Assessment:</strong> <?php echo htmlspecialchars($assessment['title']); ?></div>
                        <div><strong>For Job:</strong> <?php echo htmlspecialchars($assessment['job_title']); ?></div>
                        <div><strong>Status:</strong> 
                            <span class="badge bg-<?php echo $assessment['status'] === 'active' ? 'success' : ($assessment['status'] === 'draft' ? 'warning' : 'secondary'); ?>">
                                <?php echo ucfirst($assessment['status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Current Questions (<?php echo count($questions); ?>)</h5>
                        <?php if (empty($questions)): ?>
                            <div class="alert alert-warning">
                                No questions added yet. Use the form below to add questions.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Question</th>
                                            <th>Type</th>
                                            <th>Points</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($questions as $index => $question): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars(substr($question['question'], 0, 50)) . (strlen($question['question']) > 50 ? '...' : ''); ?></td>
                                                <td><?php echo ucwords(str_replace('_', ' ', $question['question_type'])); ?></td>
                                                <td><?php echo $question['points']; ?></td>
                                                <td>
                                                    <form method="post" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this question?')">
                                                        <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                                        <button type="submit" name="delete_question" class="btn btn-danger btn-sm">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <hr>
                    
                    <!-- Add Question Form -->
                    <div class="mt-4">
                        <h5>Add New Question</h5>
                        <form method="post" action="" id="question-form">
                            <div class="mb-3">
                                <label for="question" class="form-label">Question Text <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="question" name="question" rows="2" required></textarea>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="question_type" class="form-label">Question Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="question_type" name="question_type" required>
                                        <option value="multiple_choice">Multiple Choice</option>
                                        <option value="checkbox">Multiple Selection</option>
                                        <option value="boolean">True/False</option>
                                        <option value="text">Text Answer</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="points" class="form-label">Points</label>
                                    <input type="number" class="form-control" id="points" name="points" min="1" value="1">
                                </div>
                                <div class="col-md-4">
                                    <label for="order" class="form-label">Display Order</label>
                                    <input type="number" class="form-control" id="order" name="order" min="0" value="<?php echo count($questions); ?>">
                                </div>
                            </div>
                            
                            <!-- Multiple choice options -->
                            <div id="options-container" class="mb-3">
                                <label class="form-label">Options <span class="text-danger">*</span></label>
                                <div class="options-list">
                                    <div class="input-group mb-2">
                                        <div class="input-group-text">
                                            <input type="radio" name="correct_option" value="0" class="correct-option" checked>
                                        </div>
                                        <input type="text" class="form-control" name="options[]" placeholder="Option 1" required>
                                        <button type="button" class="btn btn-outline-danger remove-option" disabled>
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                    <div class="input-group mb-2">
                                        <div class="input-group-text">
                                            <input type="radio" name="correct_option" value="1" class="correct-option">
                                        </div>
                                        <input type="text" class="form-control" name="options[]" placeholder="Option 2" required>
                                        <button type="button" class="btn btn-outline-danger remove-option" disabled>
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                </div>
                                <button type="button" id="add-option" class="btn btn-outline-secondary btn-sm mt-2">
                                    <i class="bi bi-plus"></i> Add Option
                                </button>
                            </div>
                            
                            <!-- Multiple selection options -->
                            <div id="checkbox-container" class="mb-3" style="display:none">
                                <label class="form-label">Options (select all correct answers) <span class="text-danger">*</span></label>
                                <div class="options-list">
                                    <div class="input-group mb-2">
                                        <div class="input-group-text">
                                            <input type="checkbox" name="correct_options[]" value="0" class="correct-option-cb">
                                        </div>
                                        <input type="text" class="form-control" name="options_cb[]" placeholder="Option 1">
                                        <button type="button" class="btn btn-outline-danger remove-option-cb" disabled>
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                    <div class="input-group mb-2">
                                        <div class="input-group-text">
                                            <input type="checkbox" name="correct_options[]" value="1" class="correct-option-cb">
                                        </div>
                                        <input type="text" class="form-control" name="options_cb[]" placeholder="Option 2">
                                        <button type="button" class="btn btn-outline-danger remove-option-cb" disabled>
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                </div>
                                <button type="button" id="add-option-cb" class="btn btn-outline-secondary btn-sm mt-2">
                                    <i class="bi bi-plus"></i> Add Option
                                </button>
                            </div>
                            
                            <!-- Boolean option -->
                            <div id="boolean-container" class="mb-3" style="display:none">
                                <label class="form-label">Correct Answer <span class="text-danger">*</span></label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="correct_boolean" id="boolean-true" value="True" checked>
                                    <label class="form-check-label" for="boolean-true">
                                        True
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="correct_boolean" id="boolean-false" value="False">
                                    <label class="form-check-label" for="boolean-false">
                                        False
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Text answer -->
                            <div id="text-container" class="mb-3" style="display:none">
                                <label for="correct_text" class="form-label">Correct Answer (case insensitive) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="correct_text" name="correct_text" placeholder="Enter the correct answer">
                                <div class="form-text">For text questions, the candidate's answer will be compared case-insensitively.</div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="save_question" class="btn btn-primary">
                                    Add Question
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="create-assessment.php?id=<?php echo $assessment_id; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-gear"></i> Assessment Settings
                        </a>
                        <a href="assessment-results.php?assessment_id=<?php echo $assessment_id; ?>" class="btn btn-primary">
                            <i class="bi bi-list-check"></i> View Results
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Question type change handler
    const questionType = document.getElementById('question_type');
    const optionsContainer = document.getElementById('options-container');
    const checkboxContainer = document.getElementById('checkbox-container');
    const booleanContainer = document.getElementById('boolean-container');
    const textContainer = document.getElementById('text-container');
    
    questionType.addEventListener('change', function() {
        // Hide all containers
        optionsContainer.style.display = 'none';
        checkboxContainer.style.display = 'none';
        booleanContainer.style.display = 'none';
        textContainer.style.display = 'none';
        
        // Show container based on selected type
        if (this.value === 'multiple_choice') {
            optionsContainer.style.display = 'block';
        } else if (this.value === 'checkbox') {
            checkboxContainer.style.display = 'block';
        } else if (this.value === 'boolean') {
            booleanContainer.style.display = 'block';
        } else if (this.value === 'text') {
            textContainer.style.display = 'block';
        }
    });
    
    // Add option button handler
    document.getElementById('add-option').addEventListener('click', function() {
        const optionsList = document.querySelector('#options-container .options-list');
        const newOption = document.createElement('div');
        newOption.className = 'input-group mb-2';
        
        const numOptions = optionsList.children.length;
        
        newOption.innerHTML = `
            <div class="input-group-text">
                <input type="radio" name="correct_option" value="${numOptions}" class="correct-option">
            </div>
            <input type="text" class="form-control" name="options[]" placeholder="Option ${numOptions + 1}" required>
            <button type="button" class="btn btn-outline-danger remove-option">
                <i class="bi bi-x"></i>
            </button>
        `;
        
        optionsList.appendChild(newOption);
        
        // Enable remove buttons if there are more than 2 options
        toggleRemoveButtons();
    });
    
    // Add checkbox option button handler
    document.getElementById('add-option-cb').addEventListener('click', function() {
        const optionsList = document.querySelector('#checkbox-container .options-list');
        const newOption = document.createElement('div');
        newOption.className = 'input-group mb-2';
        
        const numOptions = optionsList.children.length;
        
        newOption.innerHTML = `
            <div class="input-group-text">
                <input type="checkbox" name="correct_options[]" value="${numOptions}" class="correct-option-cb">
            </div>
            <input type="text" class="form-control" name="options_cb[]" placeholder="Option ${numOptions + 1}" required>
            <button type="button" class="btn btn-outline-danger remove-option-cb">
                <i class="bi bi-x"></i>
            </button>
        `;
        
        optionsList.appendChild(newOption);
        
        // Enable remove buttons if there are more than 2 options
        toggleRemoveButtons();
    });
    
    // Remove option button handler (using event delegation)
    document.addEventListener('click', function(e) {
        if (e.target && e.target.closest('.remove-option')) {
            const button = e.target.closest('.remove-option');
            button.closest('.input-group').remove();
            
            // Update radio button values
            const radioButtons = document.querySelectorAll('#options-container .correct-option');
            radioButtons.forEach((radio, index) => {
                radio.value = index;
            });
            
            // Enable/disable remove buttons
            toggleRemoveButtons();
        }
        
        if (e.target && e.target.closest('.remove-option-cb')) {
            const button = e.target.closest('.remove-option-cb');
            button.closest('.input-group').remove();
            
            // Update checkbox values
            const checkboxes = document.querySelectorAll('#checkbox-container .correct-option-cb');
            checkboxes.forEach((cb, index) => {
                cb.value = index;
            });
            
            // Enable/disable remove buttons
            toggleRemoveButtons();
        }
    });
    
    // Helper function to enable/disable remove buttons
    function toggleRemoveButtons() {
        // For multiple choice
        const optionsList = document.querySelector('#options-container .options-list');
        const removeButtons = optionsList.querySelectorAll('.remove-option');
        
        if (optionsList.children.length > 2) {
            removeButtons.forEach(btn => btn.disabled = false);
        } else {
            removeButtons.forEach(btn => btn.disabled = true);
        }
        
        // For checkboxes
        const checkboxList = document.querySelector('#checkbox-container .options-list');
        const removeCheckboxButtons = checkboxList.querySelectorAll('.remove-option-cb');
        
        if (checkboxList.children.length > 2) {
            removeCheckboxButtons.forEach(btn => btn.disabled = false);
        } else {
            removeCheckboxButtons.forEach(btn => btn.disabled = true);
        }
    }
    
    // Form submission handler
    document.getElementById('question-form').addEventListener('submit', function(e) {
        const questionType = document.getElementById('question_type').value;
        
        // For multiple choice
        if (questionType === 'multiple_choice') {
            // Copy options to the main options array
            const options = document.querySelectorAll('#options-container .options-list input[type="text"]');
            
            if (options.length < 2) {
                alert('You need at least 2 options for multiple choice questions');
                e.preventDefault();
                return;
            }
            
            // Check if at least one option is selected as correct
            const correct = document.querySelector('#options-container .options-list input[type="radio"]:checked');
            if (!correct) {
                alert('You must select a correct answer');
                e.preventDefault();
                return;
            }
        }
        
        // For checkbox
        if (questionType === 'checkbox') {
            // Copy checkbox options to the main options array
            const options = document.querySelectorAll('#checkbox-container .options-list input[type="text"]');
            const mainOptions = document.querySelectorAll('input[name="options[]"]');
            
            // Clear main options
            for (let i = mainOptions.length - 1; i >= 0; i--) {
                mainOptions[i].parentNode.removeChild(mainOptions[i]);
            }
            
            // Add checkbox options to main options
            options.forEach((option, index) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'options[]';
                input.value = option.value;
                document.getElementById('question-form').appendChild(input);
            });
            
            if (options.length < 2) {
                alert('You need at least 2 options for multiple selection questions');
                e.preventDefault();
                return;
            }
            
            // Check