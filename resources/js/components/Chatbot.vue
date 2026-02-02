<script setup lang="ts">
import { ref, nextTick, onMounted, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { 
    MessageSquare, 
    Send, 
    X, 
    Bot, 
    User, 
    Sparkles,
    ChevronDown,
    Loader2,
    Lightbulb
} from 'lucide-vue-next';
import axios from 'axios';

interface Message {
    role: 'user' | 'assistant';
    content: string;
    timestamp: Date;
}

interface SuggestionCategory {
    category: string;
    questions: string[];
}

const isOpen = ref(false);
const messages = ref<Message[]>([]);
const inputMessage = ref('');
const isLoading = ref(false);
const showSuggestions = ref(true);
const suggestions = ref<SuggestionCategory[]>([]);
const messagesContainer = ref<HTMLElement | null>(null);
const isConfigured = ref(true);

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

// Watch messages and scroll
watch(messages, () => {
    scrollToBottom();
}, { deep: true });

// Check chatbot status
const checkStatus = async () => {
    try {
        const response = await axios.get('/api/chatbot/status');
        isConfigured.value = response.data?.data?.configured ?? false;
    } catch {
        isConfigured.value = false;
    }
};

// Fetch suggestions
const fetchSuggestions = async () => {
    try {
        const response = await axios.get('/api/chatbot/suggestions');
        if (response.data.success) {
            suggestions.value = response.data.data;
        }
    } catch (error) {
        console.error('Failed to fetch suggestions:', error);
    }
};

// Send message
const sendMessage = async (messageText?: string) => {
    const text = messageText || inputMessage.value.trim();
    if (!text || isLoading.value) return;

    inputMessage.value = '';
    showSuggestions.value = false;

    // Add user message
    messages.value.push({
        role: 'user',
        content: text,
        timestamp: new Date()
    });

    isLoading.value = true;

    try {
        // Prepare conversation history (last 6 messages)
        const history = messages.value
            .slice(-6)
            .map(msg => ({
                role: msg.role,
                content: msg.content
            }));

        const response = await axios.post('/api/chatbot/chat', {
            message: text,
            conversation_history: history
        });

        if (response.data.success) {
            messages.value.push({
                role: 'assistant',
                content: response.data.message,
                timestamp: new Date()
            });
        } else {
            throw new Error(response.data.message || 'Terjadi kesalahan');
        }
    } catch (error: any) {
        const errorMessage = error.response?.data?.message 
            || error.message 
            || 'Maaf, terjadi kesalahan. Silakan coba lagi.';
        
        messages.value.push({
            role: 'assistant',
            content: errorMessage,
            timestamp: new Date()
        });
    } finally {
        isLoading.value = false;
    }
};

// Toggle chat window
const toggleChat = () => {
    isOpen.value = !isOpen.value;
    
    if (isOpen.value) {
        // Check status and fetch suggestions on first open
        if (messages.value.length === 0) {
            checkStatus();
            fetchSuggestions();
            
            // Welcome message
            messages.value.push({
                role: 'assistant',
                content: 'Halo! 👋 Saya asisten AI untuk membantu Anda menggunakan aplikasi Sistem Desa Digital.\n\nAda yang bisa saya bantu? Anda bisa bertanya tentang cara menggunakan fitur-fitur aplikasi.',
                timestamp: new Date()
            });
        }
        
        nextTick(() => scrollToBottom());
    }
};

// Handle suggestion click
const handleSuggestionClick = (question: string) => {
    sendMessage(question);
};

// Format timestamp
const formatTime = (date: Date) => {
    return new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
};

// Format message content (handle newlines)
const formatContent = (content: string) => {
    return content.replace(/\\n/g, '\n');
};

// Clear chat history
const clearChat = () => {
    messages.value = [];
    showSuggestions.value = true;
    
    // Add welcome message again
    messages.value.push({
        role: 'assistant',
        content: 'Halo! 👋 Saya asisten AI untuk membantu Anda menggunakan aplikasi Sistem Desa Digital.\n\nAda yang bisa saya bantu?',
        timestamp: new Date()
    });
};

onMounted(() => {
    // Preload status check
    checkStatus();
});
</script>

<template>
    <div class="fixed bottom-4 right-4 z-50">
        <!-- Floating Chat Button -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-75"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-75"
        >
            <Button
                v-if="!isOpen"
                @click="toggleChat"
                class="h-14 w-14 rounded-full shadow-lg bg-primary text-primary-foreground hover:bg-primary/90 border-0"
                size="icon"
            >
                <MessageSquare class="h-6 w-6" />
                <span class="absolute -top-1 -right-1 flex h-4 w-4">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary/50 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-4 w-4 bg-primary items-center justify-center">
                        <Sparkles class="h-2.5 w-2.5 text-primary-foreground" />
                    </span>
                </span>
            </Button>
        </Transition>

        <!-- Chat Window -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-4 scale-95"
        >
            <Card
                v-if="isOpen"
                class="w-[380px] h-[550px] flex flex-col shadow-2xl border overflow-hidden p-0"
            >
                <!-- Header -->
                <CardHeader class="flex flex-row items-center justify-between py-3 px-4 bg-primary text-primary-foreground rounded-t-xl border-b">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-primary-foreground/20 flex items-center justify-center">
                            <Bot class="h-5 w-5 text-primary-foreground" />
                        </div>
                        <div>
                            <CardTitle class="text-base font-semibold text-primary-foreground">Asisten AI</CardTitle>
                            <p class="text-xs text-primary-foreground/80">Siap membantu Anda</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            @click="clearChat"
                            class="h-8 w-8 text-primary-foreground/80 hover:text-primary-foreground hover:bg-primary-foreground/20"
                            title="Hapus riwayat chat"
                        >
                            <ChevronDown class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            @click="toggleChat"
                            class="h-8 w-8 text-primary-foreground/80 hover:text-primary-foreground hover:bg-primary-foreground/20"
                        >
                            <X class="h-4 w-4" />
                        </Button>
                    </div>
                </CardHeader>

                <CardContent class="flex-1 flex flex-col p-0 overflow-hidden">
                    <!-- Messages Area -->
                    <div 
                        ref="messagesContainer"
                        class="flex-1 overflow-y-auto px-4 py-4 space-y-4 bg-card"
                    >
                        <!-- Messages -->
                        <div
                            v-for="(message, index) in messages"
                            :key="index"
                            class="flex gap-3"
                            :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                        >
                            <!-- Bot Avatar -->
                            <Avatar
                                v-if="message.role === 'assistant'"
                                class="h-8 w-8 flex-shrink-0"
                            >
                                <AvatarFallback class="bg-primary text-primary-foreground">
                                    <Bot class="h-4 w-4" />
                                </AvatarFallback>
                            </Avatar>

                            <!-- Message Bubble -->
                            <div
                                class="max-w-[80%] rounded-xl px-4 py-2.5 shadow-sm"
                                :class="message.role === 'user' 
                                    ? 'bg-primary text-primary-foreground rounded-br-md' 
                                    : 'bg-card border border-border rounded-bl-md'"
                            >
                                <p 
                                    class="text-sm whitespace-pre-wrap leading-relaxed"
                                    :class="message.role === 'user' ? 'text-primary-foreground' : 'text-card-foreground'"
                                >{{ formatContent(message.content) }}</p>
                                <p 
                                    class="text-[10px] mt-1.5"
                                    :class="message.role === 'user' ? 'text-primary-foreground/70' : 'text-muted-foreground'"
                                >
                                    {{ formatTime(message.timestamp) }}
                                </p>
                            </div>

                            <!-- User Avatar -->
                            <Avatar
                                v-if="message.role === 'user'"
                                class="h-8 w-8 flex-shrink-0"
                            >
                                <AvatarFallback class="bg-secondary text-secondary-foreground">
                                    <User class="h-4 w-4" />
                                </AvatarFallback>
                            </Avatar>
                        </div>

                        <!-- Loading Indicator -->
                        <div v-if="isLoading" class="flex gap-3 justify-start">
                            <Avatar class="h-8 w-8 flex-shrink-0">
                                <AvatarFallback class="bg-primary text-primary-foreground">
                                    <Bot class="h-4 w-4" />
                                </AvatarFallback>
                            </Avatar>
                            <div class="bg-card border border-border rounded-xl rounded-bl-md px-4 py-3 shadow-sm">
                                <div class="flex gap-1.5 items-center">
                                    <Loader2 class="h-4 w-4 animate-spin text-primary" />
                                    <span class="text-sm text-muted-foreground">Mengetik...</span>
                                </div>
                            </div>
                        </div>

                        <!-- Suggestions -->
                        <div v-if="showSuggestions && suggestions.length > 0 && messages.length <= 1" class="space-y-3 mt-4">
                            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                <Lightbulb class="h-4 w-4" />
                                <span>Pertanyaan populer:</span>
                            </div>
                            <div v-for="category in suggestions.slice(0, 2)" :key="category.category" class="space-y-2">
                                <Badge variant="secondary" class="text-xs">{{ category.category }}</Badge>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="question in category.questions.slice(0, 2)"
                                        :key="question"
                                        @click="handleSuggestionClick(question)"
                                        class="text-xs px-3 py-1.5 rounded-full bg-card border border-border hover:border-primary hover:bg-accent transition-colors text-card-foreground text-left"
                                    >
                                        {{ question }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Input Area -->
                    <div class="border-t border-border p-3 bg-card">
                        <form @submit.prevent="() => sendMessage()" class="flex gap-2">
                            <Input
                                v-model="inputMessage"
                                placeholder="Ketik pertanyaan Anda..."
                                class="flex-1 rounded-full"
                                :disabled="isLoading"
                                @keydown.enter.prevent="() => sendMessage()"
                            />
                            <Button
                                type="submit"
                                :disabled="isLoading || !inputMessage.trim()"
                                size="icon"
                                class="rounded-full flex-shrink-0"
                            >
                                <Send class="h-4 w-4" />
                            </Button>
                        </form>
                    </div>
                </CardContent>
            </Card>
        </Transition>
    </div>
</template>
