<template>
  <Sheet v-model:open="open">
    <SheetTrigger as-child>
      <Button class="lg:w-auto">
        <Plus class="w-4 h-4 mr-2" />
        Submit New Actor
      </Button>
    </SheetTrigger>
    <SheetContent class="w-[1000px] sm:w-[1000px] sm:max-w-none overflow-y-auto">
      <SheetHeader class="space-y-2">
        <SheetTitle class="text-xl font-semibold">Submit Actor Information</SheetTitle>
        <SheetDescription class="text-sm text-muted-foreground">
          Please enter your first name and last name, and also provide your address.
        </SheetDescription>
      </SheetHeader>

      <!-- Form Content -->
      <div class="space-y-6 p-6">
        <!-- Email Field -->
        <div class="space-y-2">
          <Label for="email">
            Email Address <span class="text-destructive">*</span>
          </Label>
          <Input
            id="email"
            v-model="form.email"
            type="email"
            placeholder="john.doe@example.com"
            :class="{ 'border-destructive': errors.email }"
            autocomplete="off"
          />
          <p v-if="errors.email" class="text-sm text-destructive">{{ errors.email }}</p>
        </div>

        <!-- Description Field -->
        <div class="space-y-2">
          <Label for="description">
            Actor Description <span class="text-destructive">*</span>
          </Label>
          <Textarea
            id="description"
            v-model="form.description"
            rows="8"
            placeholder="My name is John Smith, I am 30 years old, 6 feet tall, weigh 180 pounds, have brown hair and blue eyes. I live at 123 Main Street, Los Angeles, CA 90210. I have been acting for 5 years and specialize in dramatic roles."
            :class="{ 'border-destructive': errors.description }"
            maxlength="2000"
            class="resize-none"
          />
          <div class="flex justify-between text-sm">
            <p v-if="errors.description" class="text-destructive">{{ errors.description }}</p>
            <p class="text-muted-foreground ml-auto">
              {{ form.description.length }}/2000 characters
            </p>
          </div>
        </div>

        <!-- Fill Sample Button -->
        <div class="flex justify-center">
          <Button
            type="button"
            variant="outline"
            @click="fillSample"
            class="text-blue-700 border-blue-300 hover:bg-blue-50"
          >
            <Copy class="h-4 w-4 mr-2" />
            Fill Sample
          </Button>
        </div>

        <!-- Helper Text -->
        <Alert>
          <AlertCircle class="h-4 w-4" />
          <AlertTitle>Tips for a good description:</AlertTitle>
          <AlertDescription>
            <ul class="list-disc list-inside space-y-1 mt-2">
              <li>Include your full name (first and last name)</li>
              <li>Mention physical attributes like height, weight, hair color, eye color</li>
              <li>Provide your complete address</li>
              <li>Add your age if comfortable sharing</li>
              <li>Be specific and detailed for better results</li>
            </ul>
          </AlertDescription>
        </Alert>

        <!-- General Error -->
        <Alert v-if="errors.general" variant="destructive">
          <AlertCircle class="h-4 w-4" />
          <AlertTitle>Error</AlertTitle>
          <AlertDescription>{{ errors.general }}</AlertDescription>
        </Alert>
      </div>

      <!-- Sheet Footer -->
      <SheetFooter class="flex flex-row justify-between items-center pt-6 border-t gap-4">
        <Button variant="outline" @click="handleCancel" :disabled="submitting" size="sm">
          <ArrowLeft class="w-4 h-4 mr-2" />
          Cancel
        </Button>
        <Button @click="submitForm" :disabled="submitting" size="sm">
          <Loader2 v-if="submitting" class="w-4 h-4 mr-2 animate-spin" />
          <Send v-else class="w-4 h-4 mr-2" />
          {{ submitting ? 'Processing...' : 'Submit Actor Information' }}
        </Button>
      </SheetFooter>

      <!-- Success Dialog -->
      <Dialog v-model:open="showSuccessDialog">
        <DialogContent class="sm:max-w-md">
          <DialogHeader>
            <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-green-100 rounded-full">
              <CheckCircle class="w-6 h-6 text-green-600" />
            </div>
            <DialogTitle class="text-center">Success!</DialogTitle>
            <DialogDescription class="text-center">
              {{ successMessage }}
            </DialogDescription>
          </DialogHeader>
          <DialogFooter class="sm:justify-center">
            <Button @click="handleSuccess" class="w-full">
              Continue
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </SheetContent>
  </Sheet>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet'
import {
  AlertCircle, ArrowLeft, Send, Loader2, CheckCircle, Copy, Plus
} from 'lucide-vue-next'

interface Props {
  csrfToken: string
  submitUrl?: string
}

interface Emits {
  (e: 'success', data: any): void
}

const props = withDefaults(defineProps<Props>(), {
  submitUrl: '/api/actors'
})

const emit = defineEmits<Emits>()

const open = ref(false)
const form = reactive({
  email: '',
  description: ''
})

const errors = reactive<Record<string, string>>({})
const submitting = ref(false)
const showSuccessDialog = ref(false)
const successMessage = ref('')

const sampleDescription = "My name is Sarah Johnson, I am a 28-year-old female actress. I am 5 feet 6 inches tall and weigh 130 pounds. I have long blonde hair and green eyes. I live at 456 Hollywood Boulevard, Los Angeles, CA 90028. I have been acting for 8 years and specialize in both dramatic and comedic roles. I have appeared in several independent films and theater productions. I am originally from Chicago but moved to Los Angeles to pursue my acting career."

const fillSample = () => {
  form.email = 'sarah.johnson@test.local'
  form.description = sampleDescription
}

const handleCancel = () => {
  open.value = false
  // Reset form when cancelled
  form.email = ''
  form.description = ''
  Object.keys(errors).forEach(key => delete errors[key])
}

const handleSuccess = () => {
  showSuccessDialog.value = false
  open.value = false
  emit('success', {})

  // Reset form after success
  form.email = ''
  form.description = ''
  Object.keys(errors).forEach(key => delete errors[key])
}

const submitForm = async () => {
  // Clear previous errors
  Object.keys(errors).forEach(key => delete errors[key])

  // Basic validation
  if (!form.email.trim()) {
    errors.email = 'Email is required.'
    return
  }

  if (!form.description.trim()) {
    errors.description = 'Actor description is required.'
    return
  }

  submitting.value = true

  try {
    const response = await fetch(props.submitUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': props.csrfToken
      },
      body: JSON.stringify({
        email: form.email,
        description: form.description
      })
    })

    const data = await response.json()

    if (response.ok) {
      successMessage.value = data.message || 'Actor information submitted successfully!'
      showSuccessDialog.value = true
    } else {
      if (data.errors) {
        Object.assign(errors, data.errors)
      } else {
        errors.general = data.message || 'An error occurred while submitting the form.'
      }
    }
  } catch (error) {
    console.error('Error submitting form:', error)
    errors.general = 'An error occurred while submitting the form. Please try again.'
  } finally {
    submitting.value = false
  }
}
</script>
