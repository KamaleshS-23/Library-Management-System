<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ask Librarian</title>
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .message.sent { margin-left: auto; }
        .message.received { margin-right: auto; }
        .quick-question:hover { background-color: #d1d5db !important; }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <?php include('sidebar.php'); ?>
        
        <!-- Main Content -->
        <main class="main-content">
<div class="card">
  <h2 class="section-title">Ask a Librarian</h2>
  <div class="section-content">
    <div id="chat-container" style="display: flex; flex-direction: column; height: 600px; max-height: 70vh; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
      <!-- Chat header -->
      <div style="padding: 15px; background-color: #1f2937; color: white; display: flex; align-items: center;">
        <i data-lucide="message-circle" style="margin-right: 10px;"></i>
        <h3 style="margin: 0;">Library Support</h3>
        <div id="chat-status" style="margin-left: auto; display: flex; align-items: center; font-size: 0.9rem;">
          <span id="status-indicator" style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background-color: #6ee7b7; margin-right: 5px;"></span>
          <span id="status-text">Online</span>
        </div>
      </div>
      
      <!-- Chat messages area -->
      <div id="chat-messages" style="flex: 1; padding: 15px; overflow-y: auto; background-color: #f9fafb;">
        <!-- Messages will be inserted here -->
        <div class="message received" style="margin-bottom: 15px; max-width: 80%;">
          <div style="font-size: 0.8rem; color: #6b7280; margin-bottom: 3px;">Library Support</div>
          <div style="background-color: white; padding: 10px 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            Hello! How can I help you today?
          </div>
          <div style="font-size: 0.7rem; color: #9ca3af; text-align: right; margin-top: 3px;">Just now</div>
        </div>
      </div>
      
      <!-- Chat input area -->
      <div style="padding: 15px; background-color: white; border-top: 1px solid #e5e7eb;">
        <form id="chat-form" style="display: flex; gap: 10px;">
          <input type="text" id="chat-input" placeholder="Type your message here..." style="flex: 1; padding: 10px 15px; border: 1px solid #e5e7eb; border-radius: 20px; outline: none;">
          <button type="submit" style="padding: 10px 15px; background-color: #3b82f6; color: white; border: none; border-radius: 20px; cursor: pointer;">
            <i data-lucide="send"></i>
          </button>
        </form>
      </div>
    </div>
    
    <div style="margin-top: 20px; display: flex; flex-wrap: wrap; gap: 10px;">
      <h3 style="width: 100%; margin-bottom: 10px;">Quick Questions:</h3>
      <button class="quick-question" style="padding: 8px 15px; background-color: #e5e7eb; border: none; border-radius: 20px; cursor: pointer;">What are your opening hours?</button>
      <button class="quick-question" style="padding: 8px 15px; background-color: #e5e7eb; border: none; border-radius: 20px; cursor: pointer;">How do I renew a book?</button>
      <button class="quick-question" style="padding: 8px 15px; background-color: #e5e7eb; border: none; border-radius: 20px; cursor: pointer;">Where can I find study spaces?</button>
      <button class="quick-question" style="padding: 8px 15px; background-color: #e5e7eb; border: none; border-radius: 20px; cursor: pointer;">How many books can I borrow?</button>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Initialize chat functionality
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const chatMessages = document.getElementById('chat-messages');
    
    // Sample responses from librarian
    const responses = [
      "Our opening hours are Monday to Friday, 9am to 8pm, and Saturday 10am to 5pm.",
      "You can renew books online through your account, by phone, or in person at the circulation desk.",
      "We have study spaces available on the 2nd and 3rd floors, including quiet study areas and group study rooms.",
      "You can borrow up to 10 items at a time for a period of 3 weeks.",
      "I'd be happy to help with that. Could you provide more details?",
      "That book should be available in the Fiction section under call number FIC AUTH.",
      "You can place a hold on that item through your online account or at the circulation desk.",
      "Late fees are $0.25 per day per item, with a maximum of $5.00 per item.",
      "We have several computer workstations available on the 1st floor near the reference section.",
      "Yes, we offer printing services. Black and white prints are $0.10 per page, color prints are $0.25 per page."
    ];
    
    // Add a new message to the chat
    function addMessage(text, isUser = false) {
      const messageDiv = document.createElement('div');
      messageDiv.className = `message ${isUser ? 'sent' : 'received'}`;
      messageDiv.style.marginBottom = '15px';
      messageDiv.style.maxWidth = '80%';
      messageDiv.style.marginLeft = isUser ? 'auto' : '0';
      
      const senderDiv = document.createElement('div');
      senderDiv.style.fontSize = '0.8rem';
      senderDiv.style.color = '#6b7280';
      senderDiv.style.marginBottom = '3px';
      senderDiv.textContent = isUser ? 'You' : 'Library Support';
      
      const textDiv = document.createElement('div');
      textDiv.style.backgroundColor = isUser ? '#3b82f6' : 'white';
      textDiv.style.color = isUser ? 'white' : 'inherit';
      textDiv.style.padding = '10px 15px';
      textDiv.style.borderRadius = '8px';
      textDiv.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
      textDiv.textContent = text;
      
      const timeDiv = document.createElement('div');
      timeDiv.style.fontSize = '0.7rem';
      timeDiv.style.color = '#9ca3af';
      timeDiv.style.textAlign = 'right';
      timeDiv.style.marginTop = '3px';
      
      // Format current time (e.g., 2:45 PM)
      const now = new Date();
      let hours = now.getHours();
      const ampm = hours >= 12 ? 'PM' : 'AM';
      hours = hours % 12;
      hours = hours ? hours : 12; // the hour '0' should be '12'
      const minutes = now.getMinutes().toString().padStart(2, '0');
      timeDiv.textContent = `${hours}:${minutes} ${ampm}`;
      
      messageDiv.appendChild(senderDiv);
      messageDiv.appendChild(textDiv);
      messageDiv.appendChild(timeDiv);
      
      chatMessages.appendChild(messageDiv);
      
      // Scroll to bottom
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Handle form submission
    chatForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const message = chatInput.value.trim();
      if (!message) return;
      
      // Add user message
      addMessage(message, true);
      
      // Clear input
      chatInput.value = '';
      
      // Simulate typing indicator
      const typingIndicator = document.createElement('div');
      typingIndicator.id = 'typing-indicator';
      typingIndicator.textContent = 'Library Support is typing...';
      typingIndicator.style.fontSize = '0.8rem';
      typingIndicator.style.color = '#6b7280';
      typingIndicator.style.fontStyle = 'italic';
      typingIndicator.style.marginBottom = '15px';
      chatMessages.appendChild(typingIndicator);
      chatMessages.scrollTop = chatMessages.scrollHeight;
      
      // Simulate delay for response
      setTimeout(() => {
        // Remove typing indicator
        const indicator = document.getElementById('typing-indicator');
        if (indicator) indicator.remove();
        
        // Get random response
        const randomResponse = responses[Math.floor(Math.random() * responses.length)];
        addMessage(randomResponse, false);
      }, 1500);
    });
    
    // Quick question buttons
    document.querySelectorAll('.quick-question').forEach(button => {
      button.addEventListener('click', function() {
        const question = this.textContent;
        chatInput.value = question;
        chatInput.focus();
      });
    });
    
    // Simulate online status changes (just for demo)
    setInterval(() => {
      const statusIndicator = document.getElementById('status-indicator');
      const statusText = document.getElementById('status-text');
      
      // Randomly change status (90% chance online, 10% offline)
      if (Math.random() > 0.1) {
        statusIndicator.style.backgroundColor = '#6ee7b7';
        statusText.textContent = 'Online';
      } else {
        statusIndicator.style.backgroundColor = '#fca5a5';
        statusText.textContent = 'Offline';
      }
    }, 10000);
    
    // Initialize Lucide icons
    lucide.createIcons();
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