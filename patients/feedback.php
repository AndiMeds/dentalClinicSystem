<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dental Clinic Feedback</title>
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Varela Round', sans-serif;
            margin: 0;
            background-color: #ffff;
            color: #333;
            height: 100vh;
            overflow: hidden;
        }

        #content {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .sidebar {

            height: 100vh;
            overflow: hidden;
        }

        .main-content {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px;
            height: 100vh;
            overflow-y: auto; /* Allow main content to scroll */
        }

        .max-w-md {
            max-width: 28rem;
            margin-top: 2rem;
            background-color: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

       
        label {
            font-size: 0.875rem;
            font-weight: bold;
            color: #4a5568;
        }

        .rating {
            display: flex;
            justify-content: center;
            flex-direction: row-reverse;
            margin-top: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .rating input {
            position: absolute;
            appearance: none;
        }

        .rating label {
            cursor: pointer;
            font-size: 30px;
            color: #666;
            padding: 0 5px;
        }

        .rating:not(:checked)>label:before {
            content: '★';
        }

        .rating>input:checked+label:hover,
        .rating>input:checked+label:hover~label,
        .rating>input:checked~label:hover,
        .rating>input:checked~label:hover~label,
        .rating>label:hover~input:checked~label {
            color: #e58e09;
        }

        .rating:not(:checked)>label:hover,
        .rating:not(:checked)>label:hover~label {
            color: #ff9e0b;
        }

        .rating>input:checked~label {
            color: #ffa723;
        }

        textarea {
            width: 100%;
            padding: 0.75rem;
            color: #4a5568;
            border: 1px solid #ccc;
            border-radius: 0.5rem;
            outline: none;
        }

        button {
            background-color: #3b82f6;
            color: white;
            font-weight: bold;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background-color: #2563eb;
        }

        svg {
            margin-right: 0.5rem;
        }

        #hamburger-menu {
            display: none;
        }

        @media (max-width: 768px) {
            #hamburger-menu {
                display: block;
                position: absolute;
                top: 20px;
                left: 20px;
                font-size: 30px;
                cursor: pointer;
            }

            #content {
                flex-direction: column;
            }

            .sidebar {
                display: none;
            }

            .sidebar.active {
                display: block;
                position: absolute;
                z-index: 1000;
                width: 100%;
                height: 100%;
                background-color: #fff;
            }
        }
    </style>
</head>

<body>
    <div id="hamburger-menu" class="hamburger">&#9776;</div>

    <div id="content">
        <?php include 'sidebar.html'; ?>

        <div class="main-content">
            <div class="max-w-md">
                <h2>Feedback</h2>
                <form id="feedbackForm" action="submit_feedback.php" method="POST">
                    <div class="mb-4">
                        <label for="rate">Rate your experience:</label>
                        <div class="rating">
                            <input value="5" name="rate" id="star5" type="radio">
                            <label for="star5"></label>
                            <input value="4" name="rate" id="star4" type="radio">
                            <label for="star4"></label>
                            <input value="3" name="rate" id="star3" type="radio">
                            <label for="star3"></label>
                            <input value="2" name="rate" id="star2" type="radio">
                            <label for="star2"></label>
                            <input value="1" name="rate" id="star1" type="radio">
                            <label for="star1"></label>
                        </div>
                        <input type="hidden" id="rating" name="rating" value="0">
                    </div>
                    <div class="mb-4">
                        <label for="comment">Your comments:</label>
                        <textarea id="comment" name="comment" rows="4"
                            placeholder="Please share your thoughts on our service..." required></textarea>
                    </div>
                    <button type="submit">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                        Submit Feedback
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Update the rating hidden input when a star is selected
        document.querySelectorAll('.rating input').forEach(function (star) {
            star.addEventListener('change', function () {
                document.getElementById('rating').value = this.value; // Set rating value to hidden input
            });
        });

        // Check rating before form submission
        document.getElementById('feedbackForm').addEventListener('submit', function (e) {
            const ratingInput = document.getElementById('rating');
            if (ratingInput.value === '0') {
                e.preventDefault();
                alert('Please select a rating before submitting.');
            }
        });

        // Hamburger menu toggle functionality for mobile
        document.getElementById('hamburger-menu').addEventListener('click', function () {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
        });
    </script>
</body>

</html>
