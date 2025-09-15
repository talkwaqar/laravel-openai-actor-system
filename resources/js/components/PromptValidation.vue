<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <nav class="flex items-center space-x-2 text-sm text-muted-foreground mb-4">
        <Button variant="link" class="p-0 h-auto text-sm hover:text-foreground" @click="navigateToApiDocs">
          API Documentation
        </Button>
        <ChevronRight class="h-4 w-4" />
        <span>Prompt Validation</span>
      </nav>
      <h1 class="text-3xl font-bold tracking-tight">Prompt Validation Endpoint</h1>
      <p class="text-muted-foreground">Get the validation prompt message for actor descriptions</p>
    </div>

    <!-- Endpoint Info -->
    <Card class="shadow-sm">
      <CardContent class="p-6">
        <div class="flex items-center space-x-4 mb-4">
          <Badge variant="secondary" class="bg-green-50 text-green-700 border-green-200 text-sm px-3 py-1">GET</Badge>
          <code class="text-lg font-mono">/api/actors/prompt-validation</code>
        </div>
        <p class="text-muted-foreground">Returns the helper text that should be displayed to users when submitting actor descriptions.</p>
      </CardContent>
    </Card>

    <!-- Live Example -->
    <Card class="shadow-sm">
      <CardContent class="p-6">
        <h2 class="text-xl font-semibold mb-4">Live Example</h2>
        <div class="space-y-4">
          <div>
            <Button @click="testEndpoint" :disabled="loading" class="inline-flex items-center">
              <Zap v-if="!loading" class="mr-2 h-4 w-4" />
              <div v-else class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"></div>
              {{ loading ? 'Testing...' : 'Test Endpoint' }}
            </Button>
          </div>
          <div v-if="response" class="space-y-2">
            <h3 class="font-medium">Response:</h3>
            <pre class="p-4 bg-muted rounded-md text-sm overflow-x-auto">{{ response }}</pre>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Request Details -->
    <div class="grid gap-6 md:grid-cols-2">
      <!-- Request -->
      <Card class="shadow-sm">
        <CardContent class="p-6">
          <h2 class="text-xl font-semibold mb-4">Request</h2>
          <div class="space-y-4">
            <div>
              <h3 class="font-medium mb-2">Method</h3>
              <code class="block p-2 bg-muted rounded text-sm">GET</code>
            </div>
            <div>
              <h3 class="font-medium mb-2">URL</h3>
              <code class="block p-2 bg-muted rounded text-sm">{{ baseUrl }}/api/actors/prompt-validation</code>
            </div>
            <div>
              <h3 class="font-medium mb-2">Headers</h3>
              <code class="block p-2 bg-muted rounded text-sm">Accept: application/json</code>
            </div>
            <div>
              <h3 class="font-medium mb-2">Parameters</h3>
              <p class="text-sm text-muted-foreground">None required</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Response -->
      <Card class="shadow-sm">
        <CardContent class="p-6">
          <h2 class="text-xl font-semibold mb-4">Response</h2>
          <div class="space-y-4">
            <div>
              <h3 class="font-medium mb-2">Status Code</h3>
              <Badge variant="secondary" class="bg-green-50 text-green-700 border-green-200">200 OK</Badge>
            </div>
            <div>
              <h3 class="font-medium mb-2">Content-Type</h3>
              <code class="block p-2 bg-muted rounded text-sm">application/json</code>
            </div>
            <div>
              <h3 class="font-medium mb-2">Response Body</h3>
              <pre class="p-3 bg-muted rounded text-sm overflow-x-auto"><code>{
  "message": "Please enter your first name and last name, and also provide your address."
}</code></pre>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- cURL Example -->
    <Card class="shadow-sm">
      <CardContent class="p-6">
        <h2 class="text-xl font-semibold mb-4">cURL Example</h2>
        <div class="relative">
          <pre class="p-4 bg-muted rounded-md text-sm overflow-x-auto"><code>curl -X GET "{{ baseUrl }}/api/actors/prompt-validation" \
     -H "Accept: application/json"</code></pre>
          <Button
            variant="ghost"
            size="sm"
            class="absolute top-2 right-2 p-2"
            @click="copyToClipboard"
          >
            <Copy v-if="!copied" class="h-4 w-4" />
            <Check v-else class="h-4 w-4" />
          </Button>
        </div>
      </CardContent>
    </Card>

    <!-- Usage Notes -->
    <Card class="shadow-sm">
      <CardContent class="p-6">
        <h2 class="text-xl font-semibold mb-4">Usage Notes</h2>
        <ul class="space-y-2 text-sm text-muted-foreground">
          <li class="flex items-start space-x-2">
            <Check class="mt-0.5 h-4 w-4 text-green-600" />
            <span>This endpoint is used to get the helper text for the actor submission form</span>
          </li>
          <li class="flex items-start space-x-2">
            <Check class="mt-0.5 h-4 w-4 text-green-600" />
            <span>No authentication required for this endpoint</span>
          </li>
          <li class="flex items-start space-x-2">
            <Check class="mt-0.5 h-4 w-4 text-green-600" />
            <span>Response is cached for performance</span>
          </li>
          <li class="flex items-start space-x-2">
            <Check class="mt-0.5 h-4 w-4 text-green-600" />
            <span>The message guides users on what information to include in their actor descriptions</span>
          </li>
        </ul>
      </CardContent>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { ChevronRight, Zap, Copy, Check } from 'lucide-vue-next'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'

const loading = ref(false)
const response = ref('')
const copied = ref(false)

const baseUrl = computed(() => {
  if (typeof window !== 'undefined') {
    return window.location.origin
  }
  return 'http://localhost:8000'
})

const navigateToApiDocs = () => {
  window.location.href = '/api/docs'
}

const testEndpoint = async () => {
  loading.value = true
  response.value = ''

  try {
    const res = await fetch('/api/actors/prompt-validation', {
      headers: {
        'Accept': 'application/json'
      }
    })
    const data = await res.json()
    response.value = JSON.stringify(data, null, 2)
  } catch (error: any) {
    response.value = 'Error: ' + error.message
  }

  loading.value = false
}

const copyToClipboard = async () => {
  const curlCommand = `curl -X GET "${baseUrl.value}/api/actors/prompt-validation" \\
     -H "Accept: application/json"`

  try {
    await navigator.clipboard.writeText(curlCommand)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch (error) {
    console.error('Failed to copy to clipboard:', error)
  }
}
</script>
