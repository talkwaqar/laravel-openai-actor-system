<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Actor Submissions</h1>
        <p class="text-muted-foreground">Manage and view all actor information submissions</p>
      </div>
      <ActorCreateSheet
        :csrf-token="csrfToken"
        :submit-url="submitUrl"
        @success="handleActorCreated"
      />
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <Card>
        <CardContent class="p-6">
          <div class="flex items-center space-x-2">
            <Users class="w-8 h-8 text-primary" />
            <div>
              <p class="text-sm font-medium text-muted-foreground">Total Actors</p>
              <p class="text-2xl font-bold">{{ statistics.total }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="p-6">
          <div class="flex items-center space-x-2">
            <CheckCircle class="w-8 h-8 text-green-600" />
            <div>
              <p class="text-sm font-medium text-muted-foreground">Processed</p>
              <p class="text-2xl font-bold">{{ statistics.processed }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="p-6">
          <div class="flex items-center space-x-2">
            <Clock class="w-8 h-8 text-yellow-600" />
            <div>
              <p class="text-sm font-medium text-muted-foreground">Pending</p>
              <p class="text-2xl font-bold">{{ statistics.pending }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="p-6">
          <div class="flex items-center space-x-2">
            <AlertTriangle class="w-8 h-8 text-red-600" />
            <div>
              <p class="text-sm font-medium text-muted-foreground">Failed</p>
              <p class="text-2xl font-bold">{{ statistics.failed }}</p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Filters -->
    <Card>
      <CardHeader>
        <CardTitle class="text-lg">Filters</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="space-y-2">
            <Label for="status-filter">Status</Label>
            <Select v-model="filters.status" @update:model-value="applyFilters">
              <SelectTrigger id="status-filter">
                <SelectValue placeholder="All Statuses" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Statuses</SelectItem>
                <SelectItem value="pending">Pending</SelectItem>
                <SelectItem value="processed">Processed</SelectItem>
                <SelectItem value="failed">Failed</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label for="gender-filter">Gender</Label>
            <Select v-model="filters.gender" @update:model-value="applyFilters">
              <SelectTrigger id="gender-filter">
                <SelectValue placeholder="All Genders" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Genders</SelectItem>
                <SelectItem value="male">Male</SelectItem>
                <SelectItem value="female">Female</SelectItem>
                <SelectItem value="other">Other</SelectItem>
                <SelectItem value="prefer_not_to_say">Prefer not to say</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label for="search">Search</Label>
            <Input
              id="search"
              v-model="filters.search"
              @input="debounceSearch"
              placeholder="Search by name..."
            />
          </div>
        </div>

        <div class="flex justify-between items-center mt-4">
          <Button variant="outline" @click="clearFilters">
            <X class="w-4 h-4 mr-2" />
            Clear Filters
          </Button>
          <Button @click="applyFilters">
            <Filter class="w-4 h-4 mr-2" />
            Apply Filters
          </Button>
        </div>
      </CardContent>
    </Card>

    <!-- Actors Table -->
    <Card>
      <CardHeader>
        <CardTitle>Actor Submissions</CardTitle>
      </CardHeader>
      <CardContent>
        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-8">
          <Loader2 class="w-8 h-8 animate-spin text-primary" />
          <span class="ml-2 text-muted-foreground">Loading actors...</span>
        </div>

        <!-- Empty State -->
        <div v-else-if="actors.length === 0" class="text-center py-8">
          <Users class="w-12 h-12 text-muted-foreground mx-auto mb-4" />
          <h3 class="text-lg font-semibold mb-2">No actors found</h3>
          <p class="text-muted-foreground mb-4">Get started by submitting your first actor.</p>
          <Button @click="$emit('create')">
            <Plus class="w-4 h-4 mr-2" />
            Submit Actor Information
          </Button>
        </div>

        <!-- Actors Table -->
        <div v-else class="rounded-md border">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Actor</TableHead>
                <TableHead>Address</TableHead>
                <TableHead>Details</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Submitted</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="actor in actors" :key="actor.uuid" class="hover:bg-muted/50">
                <TableCell>
                  <div>
                    <div class="font-medium">{{ actor.full_name || 'N/A' }}</div>
                    <div class="text-sm text-muted-foreground">{{ actor.email }}</div>
                  </div>
                </TableCell>
                <TableCell>
                  <div class="text-sm">{{ actor.address || 'N/A' }}</div>
                </TableCell>
                <TableCell>
                  <div class="text-sm space-x-2">
                    <span v-if="actor.gender">{{ formatGender(actor.gender) }}</span>
                    <span v-if="actor.height">{{ actor.height }}</span>
                  </div>
                </TableCell>
                <TableCell>
                  <Badge :variant="getStatusVariant(actor.status)">
                    {{ formatStatus(actor.status) }}
                  </Badge>
                </TableCell>
                <TableCell class="text-sm text-muted-foreground">
                  {{ formatDate(actor.created_at) }}
                </TableCell>
                <TableCell class="text-right">
                  <div class="flex items-center justify-end space-x-2">
                    <Button
                      v-if="actor.status === 'failed'"
                      variant="outline"
                      size="sm"
                      @click="retryActor(actor.uuid)"
                    >
                      <RotateCcw class="w-4 h-4 mr-1" />
                      Retry
                    </Button>
                    <Button
                      variant="ghost"
                      size="sm"
                      @click="viewActor(actor.uuid)"
                    >
                      <Eye class="w-4 h-4 mr-1" />
                      View
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import ActorCreateSheet from './ActorCreateSheet.vue'
import {
  Users, CheckCircle, Clock, AlertTriangle, X, Filter,
  Loader2, Eye, RotateCcw
} from 'lucide-vue-next'

interface Props {
  apiUrl?: string
  csrfToken?: string
  submitUrl?: string
}

interface Emits {
  (e: 'view', uuid: string): void
}

const props = withDefaults(defineProps<Props>(), {
  apiUrl: '/api/actors',
  csrfToken: '',
  submitUrl: '/api/actors'
})

const emit = defineEmits<Emits>()

const actors = ref<any[]>([])
const statistics = reactive({
  total: 0,
  processed: 0,
  pending: 0,
  failed: 0
})
const loading = ref(true)
const filters = reactive({
  status: 'all',
  gender: 'all',
  search: ''
})

let searchTimeout: NodeJS.Timeout | null = null

const fetchActors = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.status && filters.status !== 'all') params.append('status', filters.status)
    if (filters.gender && filters.gender !== 'all') params.append('gender', filters.gender)
    if (filters.search) params.append('search', filters.search)

    const response = await fetch(`${props.apiUrl}?${params}`, {
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })
    const data = await response.json()

    if (response.ok) {
      actors.value = data.data?.actors || []
      Object.assign(statistics, data.data?.statistics || {})
    }
  } catch (error) {
    console.error('Error fetching actors:', error)
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  fetchActors()
}

const clearFilters = () => {
  filters.status = 'all'
  filters.gender = 'all'
  filters.search = ''
  fetchActors()
}

const debounceSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchActors()
  }, 500)
}

const retryActor = async (uuid: string) => {
  try {
    const response = await fetch(`/api/actors/${uuid}/retry`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })

    if (response.ok) {
      fetchActors() // Refresh the list
    }
  } catch (error) {
    console.error('Error retrying actor:', error)
  }
}

const viewActor = (uuid: string) => {
  emit('view', uuid)
  window.location.href = `/actors/${uuid}`
}

const handleActorCreated = () => {
  // Refresh the actors list
  fetchActors()
}

const formatStatus = (status: string) => {
  const statusMap: Record<string, string> = {
    pending: 'Pending',
    processed: 'Processed',
    failed: 'Failed'
  }
  return statusMap[status] || status
}

const formatGender = (gender: string) => {
  const genderMap: Record<string, string> = {
    male: 'Male',
    female: 'Female',
    other: 'Other',
    prefer_not_to_say: 'Prefer not to say'
  }
  return genderMap[gender] || gender
}

const getStatusVariant = (status: string) => {
  const variantMap: Record<string, string> = {
    pending: 'secondary',
    processed: 'default',
    failed: 'destructive'
  }
  return variantMap[status] || 'secondary'
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString()
}

onMounted(() => {
  fetchActors()
})
</script>
