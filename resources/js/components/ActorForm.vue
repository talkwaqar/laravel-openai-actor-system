<template>
  <Card class="w-full max-w-4xl mx-auto">
    <CardHeader>
      <CardTitle class="text-2xl font-bold text-center">Submit Actor Information</CardTitle>
      <CardDescription class="text-center">
        Please enter your first name and last name, and also provide your address.
      </CardDescription>
    </CardHeader>

    <CardContent>
      <form @submit.prevent="submitForm" class="space-y-6">
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
            required
            autocomplete="off"
            :class="{ 'border-destructive focus-visible:ring-destructive': errors.email }"
          />
          <p v-if="errors.email" class="text-sm text-destructive">
            {{ errors.email }}
          </p>
        </div>

        <!-- Description Field -->
        <div class="space-y-2">
          <Label for="description">
            Actor Description <span class="text-destructive">*</span>
          </Label>
          <Textarea
            id="description"
            v-model="form.description"
            placeholder="My name is John Smith, I am 30 years old, 6 feet tall, weigh 180 pounds, have brown hair and blue eyes. I live at 123 Main Street, Los Angeles, CA 90210. I have been acting for 5 years and specialize in dramatic roles."
            required
            :maxlength="2000"
            rows="8"
            class="resize-none"
            :class="{ 'border-destructive focus-visible:ring-destructive': errors.description }"
          />
          <div class="flex justify-between text-sm text-muted-foreground">
            <p v-if="errors.description" class="text-destructive">
              {{ errors.description }}
            </p>
            <p class="ml-auto">
              {{ form.description.length }}/2000 characters
            </p>
          </div>
        </div>

        <!-- Helper Alert -->
        <Alert>
          <AlertCircle class="h-4 w-4" />
          <AlertTitle>Tips for a good description:</AlertTitle>
          <AlertDescription class="mt-2">
            <ul class="list-disc list-inside space-y-1 text-sm">
              <li>Include your full name (first and last name)</li>
              <li>Mention physical attributes like height, weight, hair color, eye color</li>
              <li>Provide your complete address</li>
              <li>Add your age if comfortable sharing</li>
              <li>Be specific and detailed for better results</li>
            </ul>
          </AlertDescription>
        </Alert>

        <!-- Sample Description -->
        <Card class="bg-blue-50 border-blue-200">
          <CardContent class="p-4">
            <div class="flex items-center justify-between mb-3">
              <h4 class="font-medium text-blue-900 flex items-center">
                <Lightbulb class="h-4 w-4 mr-2" />
                Sample Description
              </h4>
              <Button
                type="button"
                variant="ghost"
                size="sm"
                @click="showSample = !showSample"
                class="text-blue-700 hover:text-blue-900 hover:bg-blue-100"
              >
                {{ showSample ? 'Hide' : 'Show' }} Example
                <ChevronDown :class="['h-4 w-4 ml-1 transition-transform', { 'rotate-180': showSample }]" />
              </Button>
            </div>

            <div v-if="showSample" class="space-y-3">
              <div class="bg-white border border-blue-200 rounded-md p-3">
                <p class="text-sm text-gray-700 leading-relaxed">
                  My name is Sarah Johnson, I am 28 years old and work as a professional actress. I am 5 feet 6 inches tall and weigh 130 pounds. I have long blonde hair and green eyes. I live at 456 Hollywood Boulevard, Los Angeles, CA 90028. I have been acting for 8 years and specialize in both dramatic and comedic roles. I have appeared in several independent films and theater productions. I am originally from Chicago but moved to Los Angeles to pursue my acting career.
                </p>
              </div>
              <div class="flex justify-between items-center">
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  @click="useSample"
                  class="text-blue-700 border-blue-300 hover:bg-blue-50"
                >
                  <Copy class="h-3 w-3 mr-1" />
                  Use This Example
                </Button>
                <span class="text-xs text-blue-600">Click to copy this sample to the description field</span>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 pt-6">
          <Button
            type="button"
            variant="outline"
            class="flex-1"
            @click="redirectToActors"
          >
            <ArrowLeft class="w-4 h-4 mr-2" />
            Cancel
          </Button>

          <Button
            type="submit"
            class="flex-1"
            :disabled="submitting"
          >
            <Loader2 v-if="submitting" class="w-4 h-4 mr-2 animate-spin" />
            <Send v-else class="w-4 h-4 mr-2" />
            {{ submitting ? 'Processing...' : 'Submit Actor Information' }}
          </Button>
        </div>
      </form>
    </CardContent>

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
          <Button @click="showSuccessDialog = false" class="w-full">
            Continue
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </Card>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { AlertCircle, ArrowLeft, Send, Loader2, CheckCircle, Lightbulb, ChevronDown, Copy } from 'lucide-vue-next'

interface Props {
  csrfToken: string
  cancelUrl?: string
  submitUrl?: string
}

interface Emits {
  (e: 'cancel'): void
  (e: 'success', data: any): void
}

const props = withDefaults(defineProps<Props>(), {
  cancelUrl: '/actors',
  submitUrl: '/api/actors'
})

const emit = defineEmits<Emits>()

const form = reactive({
  email: '',
  description: ''
})

const errors = reactive<Record<string, string>>({})
const submitting = ref(false)
const showSuccessDialog = ref(false)
const successMessage = ref('')
const showSample = ref(false)

const submitForm = async () => {
  submitting.value = true

  // Clear previous errors
  Object.keys(errors).forEach(key => delete errors[key])

  try {
    const response = await fetch(props.submitUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': props.csrfToken,
        'Accept': 'application/json'
      },
      body: JSON.stringify(form)
    })

    const data = await response.json()

    if (response.ok) {
      successMessage.value = data.message || 'Actor information submitted successfully!'
      showSuccessDialog.value = true

      // Reset form
      form.email = ''
      form.description = ''

      emit('success', data)
    } else {
      if (data.errors) {
        Object.assign(errors, data.errors)
      } else {
        // Handle general error
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

const redirectToActors = () => {
  emit('cancel')
}

const sampleDescription = "My name is Sarah Johnson, I am 28 years old and work as a professional actress. I am 5 feet 6 inches tall and weigh 130 pounds. I have long blonde hair and green eyes. I live at 456 Hollywood Boulevard, Los Angeles, CA 90028. I have been acting for 8 years and specialize in both dramatic and comedic roles. I have appeared in several independent films and theater productions. I am originally from Chicago but moved to Los Angeles to pursue my acting career."

const useSample = () => {
  form.description = sampleDescription
  showSample.value = false
}
</script>
