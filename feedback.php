<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Form</title>
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .feedback-type-btn:hover {
            border-color: #3b82f6;
            background-color: #f0f9ff;
        }
        .feedback-type-btn.active {
            border-color: #3b82f6;
            background-color: #dbeafe;
        }
        .star {
            transition: all 0.2s ease;
        }
        .star:hover, .star.active {
            color: #fbbf24 !important;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <?php include('sidebar.php'); ?>
        
        <!-- Main Content -->
        <main class="main-content">
<div class="card">
  <h2 class="section-title">Share Your Feedback</h2>
  <div class="section-content">
    <div class="feedback-container" style="display: flex; gap: 30px; flex-wrap: wrap;">
      <!-- Feedback Form -->
      <div class="feedback-form-container" style="flex: 1; min-width: 300px;">
        <div class="feedback-steps" style="display: flex; justify-content: space-between; margin-bottom: 25px; position: relative;">
          <div class="step-indicator active" data-step="1" style="display: flex; flex-direction: column; align-items: center; z-index: 2;">
            <div class="step-circle" style="width: 36px; height: 36px; border-radius: 50%; background-color: #3b82f6; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">1</div>
            <div class="step-label" style="margin-top: 8px; font-size: 0.8rem; color: #3b82f6;">Feedback Type</div>
          </div>
          <div class="step-indicator" data-step="2" style="display: flex; flex-direction: column; align-items: center; z-index: 2;">
            <div class="step-circle" style="width: 36px; height: 36px; border-radius: 50%; background-color: #e5e7eb; color: #6b7280; display: flex; align-items: center; justify-content: center; font-weight: bold;">2</div>
            <div class="step-label" style="margin-top: 8px; font-size: 0.8rem; color: #6b7280;">Details</div>
          </div>
          <div class="step-indicator" data-step="3" style="display: flex; flex-direction: column; align-items: center; z-index: 2;">
            <div class="step-circle" style="width: 36px; height: 36px; border-radius: 50%; background-color: #e5e7eb; color: #6b7280; display: flex; align-items: center; justify-content: center; font-weight: bold;">3</div>
            <div class="step-label" style="margin-top: 8px; font-size: 0.8rem; color: #6b7280;">Review</div>
          </div>
          <div class="progress-line" style="position: absolute; height: 4px; background-color: #e5e7eb; top: 18px; left: 18px; right: 18px; z-index: 1;"></div>
          <div class="progress-active" style="position: absolute; height: 4px; background-color: #3b82f6; top: 18px; left: 18px; width: 0%; z-index: 1; transition: width 0.3s ease;"></div>
        </div>

        <!-- Step 1: Feedback Type -->
        <div class="feedback-step active" data-step="1">
          <h3 style="margin-top: 0; color: #111827;">What type of feedback would you like to share?</h3>
          <div class="feedback-type-options" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 15px; margin: 20px 0;">
            <button class="feedback-type-btn" data-type="suggestion" style="padding: 15px; background-color: white; border: 2px solid #e5e7eb; border-radius: 10px; cursor: pointer; transition: all 0.2s ease; text-align: center;">
              <i data-lucide="lightbulb" style="width: 24px; height: 24px; color: #f59e0b; margin-bottom: 8px;"></i>
              <div style="font-weight: 500;">Suggestion</div>
            </button>
            <button class="feedback-type-btn" data-type="compliment" style="padding: 15px; background-color: white; border: 2px solid #e5e7eb; border-radius: 10px; cursor: pointer; transition: all 0.2s ease; text-align: center;">
              <i data-lucide="thumbs-up" style="width: 24px; height: 24px; color: #10b981; margin-bottom: 8px;"></i>
              <div style="font-weight: 500;">Compliment</div>
            </button>
            <button class="feedback-type-btn" data-type="complaint" style="padding: 15px; background-color: white; border: 2px solid #e5e7eb; border-radius: 10px; cursor: pointer; transition: all 0.2s ease; text-align: center;">
              <i data-lucide="alert-circle" style="width: 24px; height: 24px; color: #ef4444; margin-bottom: 8px;"></i>
              <div style="font-weight: 500;">Complaint</div>
            </button>
            <button class="feedback-type-btn" data-type="question" style="padding: 15px; background-color: white; border: 2px solid #e5e7eb; border-radius: 10px; cursor: pointer; transition: all 0.2s ease; text-align: center;">
              <i data-lucide="help-circle" style="width: 24px; height: 24px; color: #3b82f6; margin-bottom: 8px;"></i>
              <div style="font-weight: 500;">Question</div>
            </button>
          </div>
          <div class="step-actions" style="display: flex; justify-content: flex-end; margin-top: 20px;">
            <button class="next-step-btn" style="padding: 10px 20px; background-color: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;" disabled>Next</button>
          </div>
        </div>

        <!-- Step 2: Feedback Details -->
        <div class="feedback-step" data-step="2" style="display: none;">
          <h3 style="margin-top: 0; color: #111827;">Tell us more</h3>
          <div style="margin: 20px 0;">
            <label for="feedback-title" style="display: block; margin-bottom: 8px; font-weight: 500;">Title</label>
            <input type="text" id="feedback-title" placeholder="Brief summary of your feedback" style="width: 100%; padding: 10px 15px; border: 1px solid #d1d5db; border-radius: 6px; margin-bottom: 20px;">
            
            <label for="feedback-description" style="display: block; margin-bottom: 8px; font-weight: 500;">Description</label>
            <textarea id="feedback-description" placeholder="Please provide details..." style="width: 100%; padding: 10px 15px; border: 1px solid #d1d5db; border-radius: 6px; min-height: 120px; resize: vertical;"></textarea>
            
            <div style="margin-top: 15px;">
              <label style="display: block; margin-bottom: 8px; font-weight: 500;">How would you rate your experience?</label>
              <div class="rating-stars" style="display: flex; gap: 5px;">
                <i data-lucide="star" class="star" data-rating="1" style="width: 24px; height: 24px; color: #d1d5db; cursor: pointer;"></i>
                <i data-lucide="star" class="star" data-rating="2" style="width: 24px; height: 24px; color: #d1d5db; cursor: pointer;"></i>
                <i data-lucide="star" class="star" data-rating="3" style="width: 24px; height: 24px; color: #d1d5db; cursor: pointer;"></i>
                <i data-lucide="star" class="star" data-rating="4" style="width: 24px; height: 24px; color: #d1d5db; cursor: pointer;"></i>
                <i data-lucide="star" class="star" data-rating="5" style="width: 24px; height: 24px; color: #d1d5db; cursor: pointer;"></i>
              </div>
              <input type="hidden" id="feedback-rating" value="0">
            </div>
          </div>
          <div class="step-actions" style="display: flex; justify-content: space-between; margin-top: 20px;">
            <button class="prev-step-btn" style="padding: 10px 20px; background-color: #e5e7eb; color: #4b5563; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Back</button>
            <button class="next-step-btn" style="padding: 10px 20px; background-color: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Next</button>
          </div>
        </div>

        <!-- Step 3: Review & Submit -->
        <div class="feedback-step" data-step="3" style="display: none;">
          <h3 style="margin-top: 0; color: #111827;">Review your feedback</h3>
          <div class="feedback-summary" style="background-color: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <div style="display: flex; align-items: center; margin-bottom: 15px;">
              <div id="summary-type-badge" style="padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; margin-right: 10px;"></div>
              <h4 id="summary-title" style="margin: 0; font-size: 1.1rem;"></h4>
            </div>
            <p id="summary-description" style="margin: 0 0 15px 0; color: #4b5563;"></p>
            <div style="display: flex; align-items: center;">
              <span style="font-size: 0.9rem; margin-right: 10px;">Rating:</span>
              <div id="summary-rating" style="display: flex;"></div>
            </div>
          </div>
          <div style="margin: 20px 0;">
            <label for="feedback-email" style="display: block; margin-bottom: 8px; font-weight: 500;">Email (optional - if you'd like a response)</label>
            <input type="email" id="feedback-email" placeholder="your@email.com" style="width: 100%; padding: 10px 15px; border: 1px solid #d1d5db; border-radius: 6px;">
          </div>
          <div class="step-actions" style="display: flex; justify-content: space-between; margin-top: 20px;">
            <button class="prev-step-btn" style="padding: 10px 20px; background-color: #e5e7eb; color: #4b5563; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Back</button>
            <button id="submit-feedback-btn" style="padding: 10px 20px; background-color: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px;">
              <i data-lucide="send" style="width: 16px; height: 16px;"></i>
              Submit Feedback
            </button>
          </div>
        </div>
      </div>

      <!-- Feedback Examples -->
      <div class="feedback-examples" style="flex: 1; min-width: 300px;">
        <div style="background-color: #f0fdf4; border-left: 4px solid #10b981; padding: 20px; border-radius: 0 8px 8px 0; margin-bottom: 20px;">
          <h3 style="margin-top: 0; color: #111827;">Examples of good feedback</h3>
          <div style="color: #4b5563;">
            <p><strong>Clear:</strong> "The library would be more accessible if the entrance had an automatic door."</p>
            <p><strong>Specific:</strong> "The computer lab on the 2nd floor needs more power outlets for laptops."</p>
            <p><strong>Constructive:</strong> "Extending weekend hours would help students who work during the week."</p>
          </div>
        </div>
        <div style="background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 20px; border-radius: 0 8px 8px 0;">
          <h3 style="margin-top: 0; color: #111827;">What to avoid</h3>
          <div style="color: #4b5563;">
            <p><strong>Vague:</strong> "I don't like the library."</p>
            <p><strong>Personal:</strong> "The librarian was rude to me." (Instead describe what happened)</p>
            <p><strong>Demanding:</strong> "You must fix this immediately!"</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Success Modal -->
    <div id="feedback-success-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
      <div style="background-color: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 500px; text-align: center;">
        <div style="width: 80px; height: 80px; background-color: #d1fae5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
          <i data-lucide="check" style="width: 40px; height: 40px; color: #10b981;"></i>
        </div>
        <h3 style="margin-top: 0; color: #111827; font-size: 1.5rem;">Thank You!</h3>
        <p style="color: #4b5563; margin-bottom: 25px;">Your feedback has been submitted successfully. We appreciate you helping us improve our library services.</p>
        <button id="close-success-modal" style="padding: 10px 20px; background-color: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Done</button>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Initialize variables
    let currentStep = 1;
    let feedbackData = {
      type: null,
      title: '',
      description: '',
      rating: 0,
      email: ''
    };

    // Initialize Lucide icons
    lucide.createIcons();

    // Feedback type selection
    const typeButtons = document.querySelectorAll('.feedback-type-btn');
    typeButtons.forEach(button => {
      button.addEventListener('click', function() {
        // Remove active class from all buttons
        typeButtons.forEach(btn => {
          btn.style.borderColor = '#e5e7eb';
          btn.style.boxShadow = 'none';
        });

        // Add active state to clicked button
        this.style.borderColor = '#3b82f6';
        this.style.boxShadow = '0 0 0 3px rgba(59, 130, 246, 0.2)';

        // Store selected type
        feedbackData.type = this.dataset.type;
        
        // Enable next button
        document.querySelector('.next-step-btn[data-step="1"]').disabled = false;
      });
    });

    // Star rating
    const stars = document.querySelectorAll('.star');
    stars.forEach(star => {
      star.addEventListener('click', function() {
        const rating = parseInt(this.dataset.rating);
        feedbackData.rating = rating;
        
        // Update star display
        stars.forEach((s, index) => {
          if (index < rating) {
            s.style.color = '#f59e0b';
            s.setAttribute('fill', 'currentColor');
          } else {
            s.style.color = '#d1d5db';
            s.removeAttribute('fill');
          }
        });
      });
    });

    // Navigation between steps
    function goToStep(step) {
      // Hide all steps
      document.querySelectorAll('.feedback-step').forEach(stepEl => {
        stepEl.style.display = 'none';
      });
      
      // Show current step
      document.querySelector(`.feedback-step[data-step="${step}"]`).style.display = 'block';
      
      // Update step indicators
      document.querySelectorAll('.step-indicator').forEach(indicator => {
        if (parseInt(indicator.dataset.step) <= step) {
          indicator.querySelector('.step-circle').style.backgroundColor = '#3b82f6';
          indicator.querySelector('.step-circle').style.color = 'white';
          indicator.querySelector('.step-label').style.color = '#3b82f6';
        } else {
          indicator.querySelector('.step-circle').style.backgroundColor = '#e5e7eb';
          indicator.querySelector('.step-circle').style.color = '#6b7280';
          indicator.querySelector('.step-label').style.color = '#6b7280';
        }
      });
      
      // Update progress line
      const progressPercentage = ((step - 1) / 2) * 100;
      document.querySelector('.progress-active').style.width = `${progressPercentage}%`;
      
      currentStep = step;
      
      // If going to review step, update summary
      if (step === 3) {
        updateSummary();
      }
    }

    // Next step buttons
    document.querySelectorAll('.next-step-btn').forEach(button => {
      button.addEventListener('click', function() {
        // Validate current step before proceeding
        if (currentStep === 1 && !feedbackData.type) {
          return;
        }
        
        if (currentStep === 2) {
          feedbackData.title = document.getElementById('feedback-title').value;
          feedbackData.description = document.getElementById('feedback-description').value;
          
          if (!feedbackData.title || !feedbackData.description) {
            alert('Please fill in all required fields');
            return;
          }
        }
        
        goToStep(currentStep + 1);
      });
    });

    // Previous step buttons
    document.querySelectorAll('.prev-step-btn').forEach(button => {
      button.addEventListener('click', function() {
        goToStep(currentStep - 1);
      });
    });

    // Update feedback summary
    function updateSummary() {
      const typeBadge = document.getElementById('summary-type-badge');
      const typeText = feedbackData.type.charAt(0).toUpperCase() + feedbackData.type.slice(1);
      
      // Set badge color based on type
      switch(feedbackData.type) {
        case 'suggestion':
          typeBadge.style.backgroundColor = '#fef3c7';
          typeBadge.style.color = '#92400e';
          break;
        case 'compliment':
          typeBadge.style.backgroundColor = '#d1fae5';
          typeBadge.style.color = '#065f46';
          break;
        case 'complaint':
          typeBadge.style.backgroundColor = '#fee2e2';
          typeBadge.style.color = '#991b1b';
          break;
        case 'question':
          typeBadge.style.backgroundColor = '#dbeafe';
          typeBadge.style.color = '#1e40af';
          break;
      }
      
      typeBadge.textContent = typeText;
      document.getElementById('summary-title').textContent = feedbackData.title;
      document.getElementById('summary-description').textContent = feedbackData.description;
      
      // Update rating stars
      const summaryRating = document.getElementById('summary-rating');
      summaryRating.innerHTML = '';
      for (let i = 0; i < 5; i++) {
        const star = document.createElement('i');
        star.setAttribute('data-lucide', 'star');
        star.style.width = '18px';
        star.style.height = '18px';
        star.style.marginRight = '2px';
        star.style.color = i < feedbackData.rating ? '#f59e0b' : '#d1d5db';
        if (i < feedbackData.rating) {
          star.setAttribute('fill', 'currentColor');
        }
        summaryRating.appendChild(star);
      }
      lucide.createIcons();
    }

    // Submit feedback
    document.getElementById('submit-feedback-btn').addEventListener('click', function() {
      feedbackData.email = document.getElementById('feedback-email').value;
      
      // In a real app, you would send this data to your server
      console.log('Submitting feedback:', feedbackData);
      
      // Show success modal
      document.getElementById('feedback-success-modal').style.display = 'flex';
      
      // Reset form after submission (in a real app, you might want to wait for server response)
      setTimeout(() => {
        resetForm();
      }, 3000);
    });

    // Close success modal
    document.getElementById('close-success-modal').addEventListener('click', function() {
      document.getElementById('feedback-success-modal').style.display = 'none';
      resetForm();
    });

    // Reset form to initial state
    function resetForm() {
      // Reset data
      feedbackData = {
        type: null,
        title: '',
        description: '',
        rating: 0,
        email: ''
      };
      
      // Reset UI
      typeButtons.forEach(btn => {
        btn.style.borderColor = '#e5e7eb';
        btn.style.boxShadow = 'none';
      });
      
      document.getElementById('feedback-title').value = '';
      document.getElementById('feedback-description').value = '';
      document.getElementById('feedback-email').value = '';
      
      stars.forEach(star => {
        star.style.color = '#d1d5db';
        star.removeAttribute('fill');
      });
      
      document.querySelector('.next-step-btn[data-step="1"]').disabled = true;
      
      // Go back to first step
      goToStep(1);
    }
  });
</script>
        </main>
    </div>
    <script>
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>