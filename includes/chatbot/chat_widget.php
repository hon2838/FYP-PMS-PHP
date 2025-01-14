<!-- AI Chatbot Widget -->
<div class="chat-widget-fixed minimized" id="chatWidget">
    <button class="chat-toggle" id="toggleChat">
        <i class="fas fa-comments"></i>
    </button>
    
    <div class="chat-header" id="chatHeader">
        <div class="chat-title">
            <i class="fas fa-robot me-2"></i>
            <span>SOC Assistant</span>
        </div>
        <div class="chat-controls">
            <button class="control-btn" id="minimizeChat">
                <i class="fas fa-minus"></i>
            </button>
            <button class="control-btn" id="closeChat">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    
    <div class="chat-body" id="chatBody">
        <div class="chat-messages" id="chatMessages">
            <!-- Messages will be inserted here -->
            <div class="chat-message bot">
                <div class="message-content">
                    <i class="fas fa-robot message-avatar"></i>
                    <p>Hello! I'm your SOC Assistant. How can I help you today?</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="chat-input">
        <input type="text" 
               id="userInput" 
               placeholder="Type your message..."
               aria-label="Chat message">
        <button id="sendMessage">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>