<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "MySql_987"; // Use your actual MySQL password
$dbname = "bmr_calculator"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Variables
$error = "";
$dailyCalories = null;
$userInfo = null;
$bmr = null;

// Define a class to calculate BMR
class BMRCalculator {
    private $weight;
    private $height;
    private $age;
    private $gender;

    public function __construct($weight, $height, $age, $gender) {
        $this->weight = $weight;
        $this->height = $height;
        $this->age = $age;
        $this->gender = $gender;
    }

    public function calculateBMR() {
        if ($this->gender === 'Male') {
            return 66.47 + (13.75 * $this->weight) + (5.003 * $this->height) - (6.755 * $this->age);
        } else {
            return 655.1 + (9.563 * $this->weight) + (1.850 * $this->height) - (4.676 * $this->age);
        }
    }
}

// Extend BMRCalculator for activity level
class ActivityLevelCalculator extends BMRCalculator {
    private $activityLevel;

    public function __construct($weight, $height, $age, $gender, $activityLevel) {
        parent::__construct($weight, $height, $age, $gender);
        $this->activityLevel = $activityLevel;
    }

    public function getMultiplier() {
        $multipliers = [
            'Sedentary' => 1.2,
            'Lightly active' => 1.375,
            'Moderately active' => 1.55,
            'Active' => 1.725,
            'Very active' => 1.9
        ];
        return isset($multipliers[$this->activityLevel]) ? $multipliers[$this->activityLevel] : 1.2;
    }

    public function calculateDailyCalories() {
        return round($this->calculateBMR() * $this->getMultiplier(), 2);
    }
}

// Handle form submission for calculation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['name'])) {
    $name = $_POST['name'];
    $gender = $_POST['gender'];
    $age = intval($_POST['age']);
    $height = intval($_POST['height']);
    $weight = intval($_POST['weight']);
    $activityLevel = $_POST['activityLevel'];
    $goal = $_POST['goal'];

    // Validate inputs
    if (!$name || !$gender || !$age || !$height || !$weight || !$activityLevel || !$goal) {
        $error = "Please fill in all fields.";
    } else {
        // Calculate daily caloric needs
        $calculator = new ActivityLevelCalculator($weight, $height, $age, $gender, $activityLevel);
        $dailyCalories = $calculator->calculateDailyCalories();
        $bmr = $calculator->calculateBMR();

        // Store data in database
        $sql = "INSERT INTO users (name, gender, age, height, weight, activityLevel, goal, bmr, dailyCalories) VALUES 
                ('$name', '$gender', $age, $height, $weight, '$activityLevel', '$goal', $bmr, $dailyCalories)";
        if (!$conn->query($sql)) {
            $error = "Error saving data: " . $conn->error;
        }
    }
}

// Handle search by name
if (isset($_POST['searchName'])) {
    $searchName = $conn->real_escape_string($_POST['searchName']);
    $sql = "SELECT * FROM users WHERE name = '$searchName'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $userInfo = $result->fetch_assoc();
    } else {
        $error = "No user found with the name '$searchName'.";
    }
}

// Handle edit request
if (isset($_POST['edit']) && isset($userInfo)) {
    $name = $_POST['name'];
    $gender = $_POST['gender'];
    $age = intval($_POST['age']);
    $height = intval($_POST['height']);
    $weight = intval($_POST['weight']);
    $activityLevel = $_POST['activityLevel'];
    $goal = $_POST['goal'];

    $sql = "UPDATE users SET name='$name', gender='$gender', age=$age, height=$height, weight=$weight, activityLevel='$activityLevel', goal='$goal' WHERE id=".$userInfo['id'];
    
    if (!$conn->query($sql)) {
        $error = "Error updating data: " . $conn->error;
    } else {
        // Reset userInfo after update
        $userInfo = null;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BMR & Caloric Needs Calculator</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(to right, #f0f4f8, #cfd9e6);
        }

        .hero {
            background: linear-gradient(rgba(0, 0, 50, 0.7), rgba(0, 0, 70, 0.7)), url('hero-bg.jpg') no-repeat center center/cover;
            color: white;
            padding: 100px 20px;
            text-align: center;
        }

        .container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-gap: 20px;
        }

        label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        input, select {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 1rem;
        }

        button {
            grid-column: span 2;
            padding: 15px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            font-size: 1.2rem;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            transition: background 0.3s;
        }

        button:hover {
            background: linear-gradient(135deg, #0056b3, #003580);
        }

        .result, .search-result {
            padding: 20px;
            background-color: #e9f1f7;
            border-radius: 10px;
            margin-top: 20px;
            font-size: 1.1rem;
        }

        .search-section {
            margin-top: 50px;
            text-align: center;
        }

        .search-section input {
            padding: 10px;
            width: 300px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 1rem;
        }

        .search-section button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            margin-left: 10px;
            border-radius: 5px;
        }

        .error {
            color: red;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <div class="hero">
        <h1>BMR & Caloric Needs Calculator</h1>
        <p>Get an accurate estimate of your daily caloric needs based on your activity level and goal.</p>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Form Card -->
        <div class="card">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" required value="<?= isset($userInfo) ? htmlspecialchars($userInfo['name']) : '' ?>">
                </div>
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" required>
                        <option value="Male" <?= isset($userInfo) && $userInfo['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= isset($userInfo) && $userInfo['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="age">Age</label>
                    <input type="number" id="age" name="age" required value="<?= isset($userInfo) ? htmlspecialchars($userInfo['age']) : '' ?>">
                </div>
                <div class="form-group">
                    <label for="height">Height (cm)</label>
                    <input type="number" id="height" name="height" required value="<?= isset($userInfo) ? htmlspecialchars($userInfo['height']) : '' ?>">
                </div>
                <div class="form-group">
                    <label for="weight">Weight (kg)</label>
                    <input type="number" id="weight" name="weight" required value="<?= isset($userInfo) ? htmlspecialchars($userInfo['weight']) : '' ?>">
                </div>
                <div class="form-group">
                    <label for="activityLevel">Activity Level</label>
                    <select id="activityLevel" name="activityLevel" required>
                        <option value="Sedentary" <?= isset($userInfo) && $userInfo['activityLevel'] == 'Sedentary' ? 'selected' : '' ?>>Sedentary</option>
                        <option value="Lightly active" <?= isset($userInfo) && $userInfo['activityLevel'] == 'Lightly active' ? 'selected' : '' ?>>Lightly active</option>
                        <option value="Moderately active" <?= isset($userInfo) && $userInfo['activityLevel'] == 'Moderately active' ? 'selected' : '' ?>>Moderately active</option>
                        <option value="Active" <?= isset($userInfo) && $userInfo['activityLevel'] == 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Very active" <?= isset($userInfo) && $userInfo['activityLevel'] == 'Very active' ? 'selected' : '' ?>>Very active</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="goal">Goal</label>
                    <select id="goal" name="goal" required>
                        <option value="Weight Loss" <?= isset($userInfo) && $userInfo['goal'] == 'Weight Loss' ? 'selected' : '' ?>>Weight Loss</option>
                        <option value="Weight Maintenance" <?= isset($userInfo) && $userInfo['goal'] == 'Weight Maintenance' ? 'selected' : '' ?>>Weight Maintenance</option>
                        <option value="Weight Gain" <?= isset($userInfo) && $userInfo['goal'] == 'Weight Gain' ? 'selected' : '' ?>>Weight Gain</option>
                    </select>
                </div>
                <button type="submit">Calculate BMR</button>
            </form>
            <?php if (isset($dailyCalories)): ?>
            <div class="result">
                <h3>Results</h3>
                <p>Your BMR is: <?= round($bmr, 2) ?> calories/day.</p>
                <p>Your estimated daily caloric needs are: <?= round($dailyCalories, 2) ?> calories/day.</p>
            </div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
            <?php endif; ?>
        </div>

        <!-- Search Card -->
        <div class="card search-section">
            <h2>Search User Data</h2>
            <form method="POST" action="">
                <input type="text" name="searchName" placeholder="Enter name to search" required>
                <button type="submit">Search</button>
            </form>
            <?php if (isset($userInfo)): ?>
            <div class="search-result">
                <h3>Details for <?= htmlspecialchars($userInfo['name']) ?></h3>
                <p>Gender: <?= htmlspecialchars($userInfo['gender']) ?></p>
                <p>Age: <?= htmlspecialchars($userInfo['age']) ?></p>
                <p>Height: <?= htmlspecialchars($userInfo['height']) ?> cm</p>
                <p>Weight: <?= htmlspecialchars($userInfo['weight']) ?> kg</p>
                <p>Activity Level: <?= htmlspecialchars($userInfo['activityLevel']) ?></p>
                <p>Goal: <?= htmlspecialchars($userInfo['goal']) ?></p>
                <form method="POST" action="">
                    <input type="hidden" name="name" value="<?= htmlspecialchars($userInfo['name']) ?>">
                    <input type="hidden" name="gender" value="<?= htmlspecialchars($userInfo['gender']) ?>">
                    <input type="hidden" name="age" value="<?= $userInfo['age'] ?>">
                    <input type="hidden" name="height" value="<?= $userInfo['height'] ?>">
                    <input type="hidden" name="weight" value="<?= $userInfo['weight'] ?>">
                    <input type="hidden" name="activityLevel" value="<?= htmlspecialchars($userInfo['activityLevel']) ?>">
                    <input type="hidden" name="goal" value="<?= htmlspecialchars($userInfo['goal']) ?>">
                    <button type="submit" name="edit" value="<?= $userInfo['id'] ?>">Edit Details</button>
                </form>
            </div>
            <?php elseif (isset($error)): ?>
            <p class="error"><?= $error ?></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
