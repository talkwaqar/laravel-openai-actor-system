<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <nav class="flex items-center space-x-2 text-sm text-muted-foreground mb-4">
        <Button variant="link" class="p-0 h-auto text-sm hover:text-foreground" @click="navigateToApiDocs">
          API Documentation
        </Button>
        <ChevronRight class="h-4 w-4" />
        <span>Actors API</span>
      </nav>
      <h1 class="text-3xl font-bold tracking-tight">Actors API</h1>
      <p class="text-muted-foreground">Complete CRUD operations for actor submissions and data management</p>
    </div>

    <!-- Endpoints Overview -->
    <div class="grid gap-4 md:grid-cols-2">
      <!-- GET /api/actors -->
      <Card class="shadow-sm">
        <CardContent class="p-4">
          <div class="flex items-center space-x-2 mb-2">
            <Badge variant="secondary" class="bg-blue-50 text-blue-700 border-blue-200">GET</Badge>
            <code class="text-sm">/api/actors</code>
          </div>
          <p class="text-sm text-muted-foreground">List all actor submissions with pagination and filtering</p>
        </CardContent>
      </Card>

      <!-- POST /api/actors -->
      <Card class="shadow-sm">
        <CardContent class="p-4">
          <div class="flex items-center space-x-2 mb-2">
            <Badge variant="secondary" class="bg-green-50 text-green-700 border-green-200">POST</Badge>
            <code class="text-sm">/api/actors</code>
          </div>
          <p class="text-sm text-muted-foreground">Submit new actor information for processing</p>
        </CardContent>
      </Card>

      <!-- GET /api/actors/{uuid} -->
      <Card class="shadow-sm">
        <CardContent class="p-4">
          <div class="flex items-center space-x-2 mb-2">
            <Badge variant="secondary" class="bg-blue-50 text-blue-700 border-blue-200">GET</Badge>
            <code class="text-sm">/api/actors/{uuid}</code>
          </div>
          <p class="text-sm text-muted-foreground">Get details of a specific actor submission</p>
        </CardContent>
      </Card>

      <!-- POST /api/actors/{uuid}/retry -->
      <Card class="shadow-sm">
        <CardContent class="p-4">
          <div class="flex items-center space-x-2 mb-2">
            <Badge variant="secondary" class="bg-orange-50 text-orange-700 border-orange-200">POST</Badge>
            <code class="text-sm">/api/actors/{uuid}/retry</code>
          </div>
          <p class="text-sm text-muted-foreground">Retry processing for a failed actor submission</p>
        </CardContent>
      </Card>
    </div>

    <!-- GET /api/actors -->
    <Card class="shadow-sm">
      <CardContent class="p-6">
        <h2 class="text-xl font-semibold mb-4">GET /api/actors</h2>
        <p class="text-muted-foreground mb-4">Retrieve a paginated list of all actor submissions with optional filtering.</p>

        <div class="grid gap-6 md:grid-cols-2">
          <div>
            <h3 class="font-medium mb-2">Query Parameters</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <code>status</code>
                <span class="text-muted-foreground">pending, processed, failed</span>
              </div>
              <div class="flex justify-between">
                <code>gender</code>
                <span class="text-muted-foreground">male, female, other</span>
              </div>
              <div class="flex justify-between">
                <code>search</code>
                <span class="text-muted-foreground">Search by name</span>
              </div>
              <div class="flex justify-between">
                <code>per_page</code>
                <span class="text-muted-foreground">Items per page (max 50)</span>
              </div>
            </div>
          </div>
          <div>
            <h3 class="font-medium mb-2">Example Response</h3>
            <pre class="p-3 bg-muted rounded text-xs overflow-x-auto"><code>{
  "success": true,
  "data": {
    "actors": [...],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 0
    },
    "statistics": {...}
  }
}</code></pre>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- POST /api/actors -->
    <Card class="shadow-sm">
      <CardContent class="p-6">
        <h2 class="text-xl font-semibold mb-4">POST /api/actors</h2>
        <p class="text-muted-foreground mb-4">Submit new actor information for processing with OpenAI API.</p>

        <div class="grid gap-6 md:grid-cols-2">
          <div>
            <h3 class="font-medium mb-2">Request Body</h3>
            <pre class="p-3 bg-muted rounded text-sm overflow-x-auto"><code>{
  "email": "actor@example.com",
  "description": "My name is John Smith..."
}</code></pre>
            <div class="mt-4">
              <h4 class="font-medium mb-2">Validation Rules</h4>
              <ul class="text-sm text-muted-foreground space-y-1">
                <li>• Email: required, valid email, unique</li>
                <li>• Description: required, min 5 words, unique</li>
              </ul>
            </div>
          </div>
          <div>
            <h3 class="font-medium mb-2">Success Response (201)</h3>
            <pre class="p-3 bg-muted rounded text-xs overflow-x-auto"><code>{
  "success": true,
  "message": "Actor information submitted successfully",
  "data": {
    "actor": {
      "uuid": "...",
      "email": "...",
      "status": "pending"
    },
    "redirect_url": "/actors"
  }
}</code></pre>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Error Responses -->
    <Card class="shadow-sm">
      <CardContent class="p-6">
        <h2 class="text-xl font-semibold mb-4">Error Responses</h2>
        <div class="grid gap-4 md:grid-cols-3">
          <div>
            <h3 class="font-medium mb-2">422 Validation Error</h3>
            <pre class="p-3 bg-muted rounded text-xs overflow-x-auto"><code>{
  "error": "validation_failed",
  "message": "The provided data is invalid.",
  "errors": {
    "email": ["Email is required."]
  }
}</code></pre>
          </div>
          <div>
            <h3 class="font-medium mb-2">429 Rate Limited</h3>
            <pre class="p-3 bg-muted rounded text-xs overflow-x-auto"><code>{
  "message": "Too Many Attempts.",
  "retry_after": 60
}</code></pre>
          </div>
          <div>
            <h3 class="font-medium mb-2">500 Processing Error</h3>
            <pre class="p-3 bg-muted rounded text-xs overflow-x-auto"><code>{
  "success": false,
  "error": "openai_processing_failed",
  "message": "Unable to process actor information"
}</code></pre>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Authentication -->
    <Card class="shadow-sm">
      <CardContent class="p-6">
        <h2 class="text-xl font-semibold mb-4">Authentication & Security</h2>
        <div class="grid gap-6 md:grid-cols-2">
          <div>
            <h3 class="font-medium mb-2">CSRF Protection</h3>
            <p class="text-sm text-muted-foreground mb-2">POST requests require CSRF token in header:</p>
            <code class="block p-2 bg-muted rounded text-sm">X-CSRF-TOKEN: {token}</code>
          </div>
          <div>
            <h3 class="font-medium mb-2">Rate Limiting</h3>
            <p class="text-sm text-muted-foreground mb-2">POST endpoints are rate limited:</p>
            <ul class="text-sm text-muted-foreground">
              <li>• 60 requests per minute</li>
              <li>• Based on IP address</li>
            </ul>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- cURL Examples -->
    <Card class="shadow-sm">
      <CardContent class="p-6">
        <h2 class="text-xl font-semibold mb-4">cURL Examples</h2>
        <div class="space-y-4">
          <div>
            <h3 class="font-medium mb-2">List Actors</h3>
            <div class="relative">
              <pre class="p-3 bg-muted rounded text-sm overflow-x-auto"><code>curl -X GET "{{ baseUrl }}/api/actors?status=processed&per_page=10" \
     -H "Accept: application/json"</code></pre>
              <Button 
                variant="ghost" 
                size="sm" 
                class="absolute top-2 right-2 p-2" 
                @click="copyListActors"
              >
                <Copy v-if="!copiedListActors" class="h-4 w-4" />
                <Check v-else class="h-4 w-4" />
              </Button>
            </div>
          </div>
          <div>
            <h3 class="font-medium mb-2">Submit Actor</h3>
            <div class="relative">
              <pre class="p-3 bg-muted rounded text-sm overflow-x-auto"><code>curl -X POST "{{ baseUrl }}/api/actors" \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -H "X-CSRF-TOKEN: {token}" \
     -d '{"email":"actor@example.com","description":"John Smith, 30 years old..."}'</code></pre>
              <Button 
                variant="ghost" 
                size="sm" 
                class="absolute top-2 right-2 p-2" 
                @click="copySubmitActor"
              >
                <Copy v-if="!copiedSubmitActor" class="h-4 w-4" />
                <Check v-else class="h-4 w-4" />
              </Button>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { ChevronRight, Copy, Check } from 'lucide-vue-next'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'

const copiedListActors = ref(false)
const copiedSubmitActor = ref(false)

const baseUrl = computed(() => {
  if (typeof window !== 'undefined') {
    return window.location.origin
  }
  return 'http://localhost:8000'
})

const navigateToApiDocs = () => {
  window.location.href = '/api/docs'
}

const copyListActors = async () => {
  const curlCommand = `curl -X GET "${baseUrl.value}/api/actors?status=processed&per_page=10" \\
     -H "Accept: application/json"`
  
  try {
    await navigator.clipboard.writeText(curlCommand)
    copiedListActors.value = true
    setTimeout(() => {
      copiedListActors.value = false
    }, 2000)
  } catch (error) {
    console.error('Failed to copy to clipboard:', error)
  }
}

const copySubmitActor = async () => {
  const curlCommand = `curl -X POST "${baseUrl.value}/api/actors" \\
     -H "Accept: application/json" \\
     -H "Content-Type: application/json" \\
     -H "X-CSRF-TOKEN: {token}" \\
     -d '{"email":"actor@example.com","description":"John Smith, 30 years old..."}'`
  
  try {
    await navigator.clipboard.writeText(curlCommand)
    copiedSubmitActor.value = true
    setTimeout(() => {
      copiedSubmitActor.value = false
    }, 2000)
  } catch (error) {
    console.error('Failed to copy to clipboard:', error)
  }
}
</script>
