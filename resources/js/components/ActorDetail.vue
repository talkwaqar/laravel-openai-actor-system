<template>
  <div class="space-y-6">
    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center min-h-[400px]">
      <div class="text-center">
        <Loader2 class="h-8 w-8 animate-spin mx-auto mb-4" />
        <p class="text-muted-foreground">Loading actor details...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="flex items-center justify-center min-h-[400px]">
      <Card class="w-full max-w-md">
        <CardContent class="p-6 text-center">
          <AlertTriangle class="h-12 w-12 text-destructive mx-auto mb-4" />
          <h3 class="text-lg font-semibold mb-2">Actor Not Found</h3>
          <p class="text-muted-foreground mb-4">{{ error }}</p>
          <Button @click="goBack" variant="outline">
            <ArrowLeft class="h-4 w-4 mr-2" />
            Go Back
          </Button>
        </CardContent>
      </Card>
    </div>

    <!-- Actor Details -->
    <div v-else-if="actor" class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <div class="flex items-center space-x-2 mb-2">
            <Button variant="ghost" size="sm" @click="goBack">
              <ArrowLeft class="h-4 w-4 mr-2" />
              Back to Actors
            </Button>
          </div>
          <h1 class="text-3xl font-bold tracking-tight">{{ actor.full_name || 'Actor Details' }}</h1>
          <p class="text-muted-foreground">Submitted {{ formatDate(actor.created_at) }}</p>
        </div>
        <div class="flex items-center space-x-2">
          <Badge :variant="getStatusVariant(actor.status)" class="text-sm">
            {{ formatStatus(actor.status) }}
          </Badge>
          <Button
            v-if="actor.status === 'failed'"
            variant="outline"
            @click="retryProcessing"
            :disabled="retrying"
          >
            <RotateCcw v-if="!retrying" class="h-4 w-4 mr-2" />
            <Loader2 v-else class="h-4 w-4 mr-2 animate-spin" />
            {{ retrying ? 'Retrying...' : 'Retry Processing' }}
          </Button>
        </div>
      </div>

      <!-- Actor Information Cards -->
      <div class="grid gap-6 md:grid-cols-2">
        <!-- Personal Information -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center">
              <User class="h-5 w-5 mr-2" />
              Personal Information
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <Label class="text-sm font-medium text-muted-foreground">First Name</Label>
                <p class="text-sm font-medium">{{ actor.first_name || 'N/A' }}</p>
              </div>
              <div>
                <Label class="text-sm font-medium text-muted-foreground">Last Name</Label>
                <p class="text-sm font-medium">{{ actor.last_name || 'N/A' }}</p>
              </div>
              <div>
                <Label class="text-sm font-medium text-muted-foreground">Email</Label>
                <p class="text-sm font-medium">{{ actor.email }}</p>
              </div>
              <div>
                <Label class="text-sm font-medium text-muted-foreground">Age</Label>
                <p class="text-sm font-medium">{{ actor.age || 'N/A' }}</p>
              </div>
              <div>
                <Label class="text-sm font-medium text-muted-foreground">Gender</Label>
                <p class="text-sm font-medium">{{ actor.display_gender || 'N/A' }}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Physical Attributes -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center">
              <Ruler class="h-5 w-5 mr-2" />
              Physical Attributes
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <Label class="text-sm font-medium text-muted-foreground">Height</Label>
                <p class="text-sm font-medium">{{ actor.height || 'N/A' }}</p>
              </div>
              <div>
                <Label class="text-sm font-medium text-muted-foreground">Weight</Label>
                <p class="text-sm font-medium">{{ actor.weight || 'N/A' }}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Address Information -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center">
              <MapPin class="h-5 w-5 mr-2" />
              Address
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-sm font-medium">{{ actor.address || 'N/A' }}</p>
          </CardContent>
        </Card>

        <!-- Processing Information -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center">
              <Clock class="h-5 w-5 mr-2" />
              Processing Status
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <Label class="text-sm font-medium text-muted-foreground">Status</Label>
              <div class="flex items-center space-x-2 mt-1">
                <Badge :variant="getStatusVariant(actor.status)">
                  {{ formatStatus(actor.status) }}
                </Badge>
              </div>
            </div>
            <div v-if="actor.processed_at">
              <Label class="text-sm font-medium text-muted-foreground">Processed At</Label>
              <p class="text-sm font-medium">{{ formatDate(actor.processed_at) }}</p>
            </div>
            <div>
              <Label class="text-sm font-medium text-muted-foreground">Submitted At</Label>
              <p class="text-sm font-medium">{{ formatDate(actor.created_at) }}</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Original Description -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center">
            <FileText class="h-5 w-5 mr-2" />
            Original Description
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="bg-muted p-4 rounded-md">
            <p class="text-sm leading-relaxed">{{ actor.original_description || 'No description available' }}</p>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import { 
  ArrowLeft, User, Ruler, MapPin, Clock, FileText, 
  Loader2, AlertTriangle, RotateCcw 
} from 'lucide-vue-next'

interface Props {
  uuid: string
  backUrl?: string
}

interface Actor {
  uuid: string
  email: string
  first_name?: string
  last_name?: string
  full_name?: string
  address?: string
  height?: string
  weight?: string
  gender?: string
  display_gender?: string
  age?: number
  status: string
  original_description?: string
  created_at: string
  processed_at?: string
}

const props = withDefaults(defineProps<Props>(), {
  backUrl: '/actors'
})

const actor = ref<Actor | null>(null)
const loading = ref(true)
const error = ref('')
const retrying = ref(false)

const fetchActor = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const response = await fetch(`/api/actors/${props.uuid}`, {
      headers: {
        'Accept': 'application/json'
      }
    })
    
    const data = await response.json()
    
    if (response.ok && data.success) {
      actor.value = data.data.actor
    } else {
      error.value = data.message || 'Actor not found'
    }
  } catch (err: any) {
    error.value = 'Failed to load actor details'
    console.error('Error fetching actor:', err)
  } finally {
    loading.value = false
  }
}

const retryProcessing = async () => {
  if (!actor.value) return
  
  retrying.value = true
  try {
    const response = await fetch(`/api/actors/${actor.value.uuid}/retry`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    
    if (response.ok) {
      // Refresh actor data
      await fetchActor()
    }
  } catch (err) {
    console.error('Error retrying actor processing:', err)
  } finally {
    retrying.value = false
  }
}

const goBack = () => {
  window.location.href = props.backUrl
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatStatus = (status: string) => {
  const statusMap: Record<string, string> = {
    pending: 'Pending',
    processed: 'Processed',
    failed: 'Failed'
  }
  return statusMap[status] || status
}

const getStatusVariant = (status: string) => {
  const variantMap: Record<string, string> = {
    pending: 'secondary',
    processed: 'default',
    failed: 'destructive'
  }
  return variantMap[status] || 'secondary'
}

onMounted(() => {
  fetchActor()
})
</script>
