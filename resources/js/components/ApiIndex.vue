<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-3xl font-bold tracking-tight">API Documentation</h1>
      <p class="text-muted-foreground">Complete reference for the Actor Management System API</p>
    </div>

    <!-- Overview Cards -->
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
      <!-- Prompt Validation Endpoint -->
      <Card class="shadow-sm">
        <CardContent class="p-6">
          <div class="flex items-center space-x-2 mb-4">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-100 text-green-600">
              <CheckCircle class="h-4 w-4" />
            </div>
            <h3 class="font-semibold">Prompt Validation</h3>
          </div>
          <p class="text-sm text-muted-foreground mb-4">Get validation prompt message for actor descriptions</p>
          <div class="space-y-2">
            <div class="flex items-center space-x-2">
              <Badge variant="secondary" class="bg-green-50 text-green-700 border-green-200">GET</Badge>
              <code class="text-sm">/api/actors/prompt-validation</code>
            </div>
            <Button variant="link" class="p-0 h-auto text-sm" @click="navigateToPromptValidation">
              View Details
              <ChevronRight class="ml-1 h-3 w-3" />
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- Actors API -->
      <Card class="shadow-sm">
        <CardContent class="p-6">
          <div class="flex items-center space-x-2 mb-4">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
              <Users class="h-4 w-4" />
            </div>
            <h3 class="font-semibold">Actors Management</h3>
          </div>
          <p class="text-sm text-muted-foreground mb-4">CRUD operations for actor submissions and data</p>
          <div class="space-y-2">
            <div class="flex items-center space-x-2">
              <Badge variant="secondary" class="bg-blue-50 text-blue-700 border-blue-200">Multiple</Badge>
              <code class="text-sm">/api/actors/*</code>
            </div>
            <Button variant="link" class="p-0 h-auto text-sm" @click="navigateToActorsApi">
              View Details
              <ChevronRight class="ml-1 h-3 w-3" />
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- API Health -->
      <Card class="shadow-sm">
        <CardContent class="p-6">
          <div class="flex items-center space-x-2 mb-4">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
              <Zap class="h-4 w-4" />
            </div>
            <h3 class="font-semibold">System Health</h3>
          </div>
          <p class="text-sm text-muted-foreground mb-4">Check API system status and health metrics</p>
          <div class="space-y-2">
            <div class="flex items-center space-x-2">
              <Badge variant="secondary" class="bg-purple-50 text-purple-700 border-purple-200">GET</Badge>
              <code class="text-sm">/api/health</code>
            </div>
            <Button variant="link" class="p-0 h-auto text-sm" @click="checkHealth">
              Check Status
              <ExternalLink class="ml-1 h-3 w-3" />
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Quick Start -->
    <Card class="shadow-sm">
      <CardContent class="p-6">
        <h2 class="text-xl font-semibold mb-4">Quick Start</h2>
        <div class="space-y-4">
          <div>
            <h3 class="font-medium mb-2">Base URL</h3>
            <code class="block p-3 bg-muted rounded-md text-sm">{{ baseUrl }}/api</code>
          </div>
          <div>
            <h3 class="font-medium mb-2">Authentication</h3>
            <p class="text-sm text-muted-foreground">Most endpoints require CSRF token for POST requests. Include the token in the <code>X-CSRF-TOKEN</code> header.</p>
          </div>
          <div>
            <h3 class="font-medium mb-2">Response Format</h3>
            <p class="text-sm text-muted-foreground">All responses are in JSON format with consistent structure including success status, data, and error information.</p>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { CheckCircle, Users, Zap, ChevronRight, ExternalLink } from 'lucide-vue-next'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'

const baseUrl = computed(() => {
  if (typeof window !== 'undefined') {
    return window.location.origin
  }
  return 'http://localhost:8000'
})

const navigateToPromptValidation = () => {
  window.location.href = '/api/docs/prompt-validation'
}

const navigateToActorsApi = () => {
  window.location.href = '/api/docs/actors'
}

const checkHealth = () => {
  window.open('/api/health', '_blank')
}
</script>
