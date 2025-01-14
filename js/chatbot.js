class ChatInterface {
    constructor() {
        this.widget = document.getElementById('chatWidget');
        this.messages = document.getElementById('chatMessages');
        this.input = document.getElementById('userInput');
        this.sendBtn = document.getElementById('sendMessage');
        this.toggleBtn = document.getElementById('toggleChat');
        this.minimizeBtn = document.getElementById('minimizeChat');
        this.closeBtn = document.getElementById('closeChat');
        
        // Mistral API configuration
        this.apiKey = '3XdLITjcDezjhDWsa1489GH3M3PEc2jF';
        this.apiEndpoint = 'https://api.mistral.ai/v1/chat/completions';
        
        // Initialize widget state
        this.widget.classList.add('chat-widget-fixed');
        this.isMinimized = true;
        
        this.setupEventListeners();
        this.loadChatHistory();
        this.initializePosition();
    }

    loadChatHistory() {
        // Initialize with welcome message
        const welcomeMessage = {
            type: 'bot',
            text: "Hello! I'm your SOC Assistant. How can I help you today?"
        };
        this.addMessage(welcomeMessage.text, welcomeMessage.type);

        // You can expand this to load chat history from localStorage or backend
        const savedHistory = localStorage.getItem('chatHistory');
        if (savedHistory) {
            const history = JSON.parse(savedHistory);
            history.forEach(msg => {
                this.addMessage(msg.text, msg.type);
            });
        }
    }

    initializePosition() {
        // Set initial position
        this.widget.style.position = 'fixed';
        this.widget.style.bottom = '20px';
        this.widget.style.right = '20px';
        this.widget.style.zIndex = '9999';
    }

    showTypingIndicator() {
        const indicator = document.createElement('div');
        indicator.className = 'chat-message bot typing';
        indicator.innerHTML = `
            <div class="message-content typing-indicator">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            </div>
        `;
        this.messages.appendChild(indicator);
        this.messages.scrollTop = this.messages.scrollHeight;
    }

    hideTypingIndicator() {
        const typingIndicator = this.messages.querySelector('.typing');
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }
    
    setupEventListeners() {
        // Send message when button clicked or Enter pressed
        this.sendBtn.addEventListener('click', () => {
            const message = this.input.value.trim();
            if (message) {
                this.handleUserMessage(message);
                this.input.value = '';
            }
        });

        this.input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                const message = this.input.value.trim();
                if (message) {
                    this.handleUserMessage(message);
                    this.input.value = '';
                }
            }
        });

        // Widget controls
        this.toggleBtn.addEventListener('click', () => this.toggleChat());
        this.minimizeBtn.addEventListener('click', () => this.minimizeChat());
        this.closeBtn.addEventListener('click', () => this.closeChat());
    }
    
    async sendMessageToMistral(message) {
        try {
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.apiKey}`
                },
                body: JSON.stringify({
                    model: "mistral-tiny",
                    messages: [{
                        role: "user",
                        content: message
                    }],
                    temperature: 0.7
                })
            });

            if (!response.ok) {
                throw new Error('API request failed');
            }

            const data = await response.json();
            return data.choices[0].message.content;
        } catch (error) {
            console.error('Error calling Mistral API:', error);
            return 'I apologize, but I am unable to process your request at the moment.';
        }
    }

    async handleUserMessage(message) {
        // Show user message
        this.addMessage(message, 'user');
        
        // Show typing indicator
        this.showTypingIndicator();
        
        try {
            // Get response from Mistral
            const response = await this.sendMessageToMistral(message);
            
            // Hide typing indicator
            this.hideTypingIndicator();
            
            // Show bot response
            this.addMessage(response, 'bot');
            
        } catch (error) {
            this.hideTypingIndicator();
            this.addMessage('Sorry, I encountered an error processing your request.', 'bot error');
        }
    }

    addMessage(text, type) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-message ${type}`;
        messageDiv.innerHTML = `
            <div class="message-content">
                ${type === 'bot' ? '<i class="fas fa-robot"></i>' : ''}
                <p>${text}</p>
            </div>
        `;
        this.messages.appendChild(messageDiv);
        this.messages.scrollTop = this.messages.scrollHeight;
    }
    
    // UI Control Methods
    toggleChat() {
        if (this.isMinimized) {
            this.widget.classList.add('expanded');
            this.widget.classList.remove('minimized');
            this.input.focus();
        } else {
            this.widget.classList.remove('expanded');
            this.widget.classList.add('minimized');
        }
        this.isMinimized = !this.isMinimized;
    }
    
    minimizeChat() {
        this.widget.classList.remove('expanded');
        this.widget.classList.add('minimized');
        this.isMinimized = true;
    }
    
    closeChat() {
        this.widget.classList.remove('expanded');
        this.widget.classList.add('hidden');
        this.isMinimized = true;
        setTimeout(() => {
            this.widget.style.display = 'none';
        }, 300);
    }
}

// Initialize chat interface
document.addEventListener('DOMContentLoaded', () => {
    new ChatInterface();
});