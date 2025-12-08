# Dashboard Widgets & Metrics - Laravel Studio Package

## Overview

**Purpose:** Display KPIs, charts, and metrics on dashboard and resource pages.

**Chart Library:** Chart.js (included, pre-built components)
**Dependencies:** Chart.js (~60KB)

---

## API Design

### Resource Cards

```php
// In Resource class
class UserResource extends Resource
{
    /**
     * Define cards/metrics for this resource
     * Displayed at top of resource index page
     */
    public function cards(): array
    {
        return [
            ValueCard::make('Total Users')
                ->value(User::count())
                ->icon('users')
                ->color('blue'),

            TrendCard::make('New This Month')
                ->value(User::thisMonth()->count())
                ->previousValue(User::lastMonth()->count())
                ->format('number'),

            PartitionCard::make('By Status')
                ->data([
                    'Active' => User::where('status', 'active')->count(),
                    'Inactive' => User::where('status', 'inactive')->count(),
                ])
                ->colors(['Active' => 'green', 'Inactive' => 'gray']),
        ];
    }
}
```

### Dashboard Widgets (Panel Configuration)

```php
// config/studio.php
'panels' => [
    'admin' => [
        'widgets' => [
            // Stats row
            [
                'component' => 'StatsRow',
                'width' => 'full',
                'props' => [
                    'stats' => [
                        ['label' => 'Total Users', 'value' => 'users.count', 'icon' => 'users'],
                        ['label' => 'Active Users', 'value' => 'users.active', 'icon' => 'user-check'],
                        ['label' => 'New Today', 'value' => 'users.today', 'icon' => 'user-plus'],
                    ],
                ],
            ],

            // Chart
            [
                'component' => 'LineChart',
                'width' => '1/2',
                'props' => [
                    'title' => 'User Signups',
                    'endpoint' => '/api/analytics/signups',
                    'days' => 30,
                ],
            ],

            // Recent activity
            [
                'component' => 'RecentActivity',
                'width' => '1/2',
                'props' => [
                    'limit' => 10,
                ],
            ],

            // Table widget
            [
                'component' => 'TableWidget',
                'width' => 'full',
                'props' => [
                    'title' => 'Recent Users',
                    'resource' => 'users',
                    'columns' => ['name', 'email', 'created_at'],
                    'limit' => 5,
                ],
            ],
        ],
    ],
],
```

---

## Card Types

### 1. Value Card

Single metric display with optional icon, color, and trend.

```php
ValueCard::make('Total Revenue')
    ->id('total-revenue')                    // Unique identifier
    ->value(fn() => Order::sum('total'))     // Value (closure for lazy loading)
    ->format('currency', 'USD')              // Format: number, currency, percentage
    ->icon('dollar-sign')                    // Icon name
    ->color('green')                         // Color: blue, green, red, yellow, purple, gray
    ->trend('+12.5%')                        // Optional trend indicator
    ->helpText('Total revenue this month')  // Tooltip text
    ->link('/admin/orders')                  // Optional click link
    ->refreshInterval(60)                    // Auto-refresh in seconds
```

### 2. Trend Card

Metric with comparison to previous period.

```php
TrendCard::make('Orders This Week')
    ->id('orders-week')
    ->value(fn() => Order::thisWeek()->count())
    ->previousValue(fn() => Order::lastWeek()->count())
    ->format('number')
    ->icon('shopping-cart')
    ->increaseIsGood(true)                   // Green for increase, red for decrease
    ->suffix('orders')                       // Text after value
```

### 3. Partition Card

Breakdown of data into segments (pie/donut/bar chart).

```php
PartitionCard::make('Users by Role')
    ->id('users-by-role')
    ->data(fn() => [
        'Admin' => User::role('admin')->count(),
        'Editor' => User::role('editor')->count(),
        'User' => User::role('user')->count(),
    ])
    ->type('donut')                          // pie, donut, bar
    ->colors([
        'Admin' => '#3B82F6',
        'Editor' => '#10B981',
        'User' => '#6B7280',
    ])
```

### 4. Table Card

Mini table showing recent/top records.

```php
TableCard::make('Recent Orders')
    ->id('recent-orders')
    ->query(fn() => Order::with('customer')->latest()->limit(5)->get())
    ->columns([
        'id' => 'Order #',
        'customer.name' => 'Customer',
        'total' => ['label' => 'Total', 'format' => 'currency'],
        'status' => 'Status',
        'created_at' => ['label' => 'Date', 'format' => 'date'],
    ])
    ->link('/admin/orders')                  // "View All" link
```

### 5. Chart Card

Time series data visualization.

```php
ChartCard::make('Revenue Trend')
    ->id('revenue-trend')
    ->type('line')                           // line, bar, area
    ->data(fn() => Order::revenueByDay(30))  // Returns array of {date, value}
    ->xAxis('date')
    ->yAxis('value')
    ->yAxisFormat('currency')
    ->color('#3B82F6')
    ->fill(true)                             // Fill area under line
    ->height(300)                            // Chart height in pixels
```

### 6. Custom Card

For complex custom visualizations.

```php
CustomCard::make('System Status')
    ->id('system-status')
    ->component('SystemStatusCard')          // Vue component name
    ->props([
        'showQueue' => true,
        'showCache' => true,
    ])
    ->refreshInterval(30)
```

---

## Backend Implementation

### File Structure

```
packages/laravel-studio/
├── src/
│   ├── Cards/
│   │   ├── Card.php                         # Base class
│   │   ├── ValueCard.php
│   │   ├── TrendCard.php
│   │   ├── PartitionCard.php
│   │   ├── TableCard.php
│   │   ├── ChartCard.php
│   │   └── CustomCard.php
│   ├── Services/
│   │   ├── CardService.php
│   │   └── WidgetService.php
│   └── Http/Controllers/
│       ├── CardController.php
│       └── WidgetController.php
└── config/studio.php
```

### Base Card Class

```php
// src/Cards/Card.php
namespace SavyApps\LaravelStudio\Cards;

use Illuminate\Support\Str;

abstract class Card
{
    protected string $id;
    protected string $name;
    protected ?string $icon = null;
    protected ?string $color = null;
    protected ?string $helpText = null;
    protected ?string $link = null;
    protected ?int $refreshInterval = null;
    protected string $width = '1/3';         // 1/4, 1/3, 1/2, 2/3, 3/4, full

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->id = Str::slug($name);
    }

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function id(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function color(string $color): static
    {
        $this->color = $color;
        return $this;
    }

    public function helpText(string $text): static
    {
        $this->helpText = $text;
        return $this;
    }

    public function link(string $url): static
    {
        $this->link = $url;
        return $this;
    }

    public function refreshInterval(int $seconds): static
    {
        $this->refreshInterval = $seconds;
        return $this;
    }

    public function width(string $width): static
    {
        $this->width = $width;
        return $this;
    }

    /**
     * Get the card type for frontend component mapping
     */
    abstract public function type(): string;

    /**
     * Calculate the card data
     */
    abstract public function resolve(): array;

    /**
     * Convert to array for JSON response
     */
    public function toArray(): array
    {
        return array_merge([
            'id' => $this->id,
            'type' => $this->type(),
            'name' => $this->name,
            'icon' => $this->icon,
            'color' => $this->color,
            'helpText' => $this->helpText,
            'link' => $this->link,
            'refreshInterval' => $this->refreshInterval,
            'width' => $this->width,
        ], $this->resolve());
    }
}
```

### Value Card

```php
// src/Cards/ValueCard.php
namespace SavyApps\LaravelStudio\Cards;

class ValueCard extends Card
{
    protected $value;
    protected string $format = 'number';
    protected ?string $formatOptions = null;
    protected ?string $trend = null;
    protected ?string $prefix = null;
    protected ?string $suffix = null;

    public function value($value): static
    {
        $this->value = $value;
        return $this;
    }

    public function format(string $format, ?string $options = null): static
    {
        $this->format = $format;
        $this->formatOptions = $options;
        return $this;
    }

    public function trend(string $trend): static
    {
        $this->trend = $trend;
        return $this;
    }

    public function prefix(string $prefix): static
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function suffix(string $suffix): static
    {
        $this->suffix = $suffix;
        return $this;
    }

    public function type(): string
    {
        return 'value';
    }

    public function resolve(): array
    {
        $value = is_callable($this->value) ? call_user_func($this->value) : $this->value;

        return [
            'value' => $value,
            'format' => $this->format,
            'formatOptions' => $this->formatOptions,
            'trend' => $this->trend,
            'prefix' => $this->prefix,
            'suffix' => $this->suffix,
        ];
    }
}
```

### Trend Card

```php
// src/Cards/TrendCard.php
namespace SavyApps\LaravelStudio\Cards;

class TrendCard extends Card
{
    protected $value;
    protected $previousValue;
    protected string $format = 'number';
    protected bool $increaseIsGood = true;
    protected ?string $suffix = null;

    public function value($value): static
    {
        $this->value = $value;
        return $this;
    }

    public function previousValue($value): static
    {
        $this->previousValue = $value;
        return $this;
    }

    public function format(string $format): static
    {
        $this->format = $format;
        return $this;
    }

    public function increaseIsGood(bool $good = true): static
    {
        $this->increaseIsGood = $good;
        return $this;
    }

    public function suffix(string $suffix): static
    {
        $this->suffix = $suffix;
        return $this;
    }

    public function type(): string
    {
        return 'trend';
    }

    public function resolve(): array
    {
        $current = is_callable($this->value) ? call_user_func($this->value) : $this->value;
        $previous = is_callable($this->previousValue) ? call_user_func($this->previousValue) : $this->previousValue;

        $change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;

        return [
            'value' => $current,
            'previousValue' => $previous,
            'change' => round($change, 1),
            'changeDirection' => $change >= 0 ? 'up' : 'down',
            'format' => $this->format,
            'increaseIsGood' => $this->increaseIsGood,
            'suffix' => $this->suffix,
        ];
    }
}
```

### Partition Card

```php
// src/Cards/PartitionCard.php
namespace SavyApps\LaravelStudio\Cards;

class PartitionCard extends Card
{
    protected $data;
    protected string $chartType = 'donut';
    protected array $colors = [];

    public function data($data): static
    {
        $this->data = $data;
        return $this;
    }

    public function type(string $type): static
    {
        $this->chartType = $type;
        return $this;
    }

    public function colors(array $colors): static
    {
        $this->colors = $colors;
        return $this;
    }

    public function type(): string
    {
        return 'partition';
    }

    public function resolve(): array
    {
        $data = is_callable($this->data) ? call_user_func($this->data) : $this->data;

        return [
            'data' => $data,
            'chartType' => $this->chartType,
            'colors' => $this->colors,
            'total' => array_sum($data),
        ];
    }
}
```

### Chart Card

```php
// src/Cards/ChartCard.php
namespace SavyApps\LaravelStudio\Cards;

class ChartCard extends Card
{
    protected $data;
    protected string $chartType = 'line';
    protected string $xAxis = 'x';
    protected string $yAxis = 'y';
    protected ?string $yAxisFormat = null;
    protected string $chartColor = '#3B82F6';
    protected bool $fill = false;
    protected int $height = 200;

    public function data($data): static
    {
        $this->data = $data;
        return $this;
    }

    public function type(string $type): static
    {
        $this->chartType = $type;
        return $this;
    }

    public function xAxis(string $key): static
    {
        $this->xAxis = $key;
        return $this;
    }

    public function yAxis(string $key): static
    {
        $this->yAxis = $key;
        return $this;
    }

    public function yAxisFormat(string $format): static
    {
        $this->yAxisFormat = $format;
        return $this;
    }

    public function chartColor(string $color): static
    {
        $this->chartColor = $color;
        return $this;
    }

    public function fill(bool $fill = true): static
    {
        $this->fill = $fill;
        return $this;
    }

    public function height(int $height): static
    {
        $this->height = $height;
        return $this;
    }

    public function type(): string
    {
        return 'chart';
    }

    public function resolve(): array
    {
        $data = is_callable($this->data) ? call_user_func($this->data) : $this->data;

        return [
            'data' => $data,
            'chartType' => $this->chartType,
            'xAxis' => $this->xAxis,
            'yAxis' => $this->yAxis,
            'yAxisFormat' => $this->yAxisFormat,
            'color' => $this->chartColor,
            'fill' => $this->fill,
            'height' => $this->height,
        ];
    }
}
```

### Card Controller

```php
// src/Http/Controllers/CardController.php
namespace SavyApps\LaravelStudio\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SavyApps\LaravelStudio\Services\CardService;

class CardController extends Controller
{
    public function __construct(
        protected CardService $cardService
    ) {}

    /**
     * Get cards for a resource
     */
    public function resource(Request $request, string $resource): JsonResponse
    {
        $cards = $this->cardService->getResourceCards($resource);

        return response()->json([
            'cards' => collect($cards)->map->toArray()->values(),
        ]);
    }

    /**
     * Get a single card's data (for refresh)
     */
    public function show(Request $request, string $resource, string $cardId): JsonResponse
    {
        $card = $this->cardService->getResourceCard($resource, $cardId);

        if (!$card) {
            return response()->json(['message' => 'Card not found'], 404);
        }

        return response()->json($card->toArray());
    }

    /**
     * Get dashboard widgets
     */
    public function dashboard(Request $request, string $panel): JsonResponse
    {
        $widgets = $this->cardService->getDashboardWidgets($panel);

        return response()->json([
            'widgets' => $widgets,
        ]);
    }
}
```

### Card Service

```php
// src/Services/CardService.php
namespace SavyApps\LaravelStudio\Services;

use SavyApps\LaravelStudio\Cards\Card;

class CardService
{
    /**
     * Get cards for a resource
     */
    public function getResourceCards(string $resourceKey): array
    {
        $resourceClass = config("studio.resources.{$resourceKey}.class");

        if (!$resourceClass || !method_exists($resourceClass, 'cards')) {
            return [];
        }

        $resource = new $resourceClass();
        return $resource->cards();
    }

    /**
     * Get a specific card from a resource
     */
    public function getResourceCard(string $resourceKey, string $cardId): ?Card
    {
        $cards = $this->getResourceCards($resourceKey);

        foreach ($cards as $card) {
            if ($card->toArray()['id'] === $cardId) {
                return $card;
            }
        }

        return null;
    }

    /**
     * Get dashboard widgets for a panel
     */
    public function getDashboardWidgets(string $panel): array
    {
        return config("studio.panels.{$panel}.widgets", []);
    }
}
```

---

## Frontend Implementation

### File Structure

```
packages/laravel-studio/resources/js/
├── components/
│   └── cards/
│       ├── CardGrid.vue
│       ├── CardWrapper.vue
│       ├── ValueCard.vue
│       ├── TrendCard.vue
│       ├── PartitionCard.vue
│       ├── TableCard.vue
│       ├── ChartCard.vue
│       └── charts/
│           ├── LineChart.vue
│           ├── BarChart.vue
│           ├── AreaChart.vue
│           ├── PieChart.vue
│           └── DonutChart.vue
├── composables/
│   └── useCards.js
└── services/
    └── cardService.js
```

### Card Service

```javascript
// services/cardService.js
import api from '@/services/api'

export const cardService = {
  /**
   * Get cards for a resource
   */
  async getResourceCards(resource) {
    const response = await api.get(`/api/resources/${resource}/cards`)
    return response.data.cards
  },

  /**
   * Refresh a single card
   */
  async refreshCard(resource, cardId) {
    const response = await api.get(`/api/resources/${resource}/cards/${cardId}`)
    return response.data
  },

  /**
   * Get dashboard widgets
   */
  async getDashboardWidgets(panel) {
    const response = await api.get(`/api/panels/${panel}/widgets`)
    return response.data.widgets
  },
}

export default cardService
```

### Card Grid Component

```vue
<!-- components/cards/CardGrid.vue -->
<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { cardService } from '@/services/cardService'
import CardWrapper from './CardWrapper.vue'

const props = defineProps({
  resource: String,
  cards: Array,
})

const localCards = ref([])
const refreshTimers = ref({})

// Load cards
onMounted(async () => {
  if (props.cards) {
    localCards.value = props.cards
  } else if (props.resource) {
    localCards.value = await cardService.getResourceCards(props.resource)
  }

  // Set up auto-refresh timers
  localCards.value.forEach(card => {
    if (card.refreshInterval) {
      refreshTimers.value[card.id] = setInterval(
        () => refreshCard(card.id),
        card.refreshInterval * 1000
      )
    }
  })
})

// Clean up timers
onUnmounted(() => {
  Object.values(refreshTimers.value).forEach(timer => clearInterval(timer))
})

// Refresh a single card
const refreshCard = async (cardId) => {
  if (!props.resource) return

  const data = await cardService.refreshCard(props.resource, cardId)
  const index = localCards.value.findIndex(c => c.id === cardId)
  if (index !== -1) {
    localCards.value[index] = data
  }
}

// Width classes
const getWidthClass = (width) => {
  const classes = {
    '1/4': 'w-full md:w-1/4',
    '1/3': 'w-full md:w-1/3',
    '1/2': 'w-full md:w-1/2',
    '2/3': 'w-full md:w-2/3',
    '3/4': 'w-full md:w-3/4',
    'full': 'w-full',
  }
  return classes[width] || classes['1/3']
}
</script>

<template>
  <div class="card-grid flex flex-wrap -mx-2">
    <div
      v-for="card in localCards"
      :key="card.id"
      :class="['px-2 mb-4', getWidthClass(card.width)]"
    >
      <CardWrapper :card="card" @refresh="refreshCard(card.id)" />
    </div>
  </div>
</template>
```

### Card Wrapper Component

```vue
<!-- components/cards/CardWrapper.vue -->
<script setup>
import { computed } from 'vue'
import ValueCard from './ValueCard.vue'
import TrendCard from './TrendCard.vue'
import PartitionCard from './PartitionCard.vue'
import TableCard from './TableCard.vue'
import ChartCard from './ChartCard.vue'

const props = defineProps({
  card: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['refresh'])

const component = computed(() => {
  const components = {
    value: ValueCard,
    trend: TrendCard,
    partition: PartitionCard,
    table: TableCard,
    chart: ChartCard,
  }
  return components[props.card.type] || null
})
</script>

<template>
  <component
    :is="component"
    v-if="component"
    :card="card"
    @refresh="emit('refresh')"
  />
</template>
```

### Value Card Component

```vue
<!-- components/cards/ValueCard.vue -->
<script setup>
import { computed } from 'vue'
import Icon from '@/components/common/Icon.vue'

const props = defineProps({
  card: {
    type: Object,
    required: true,
  },
})

const formattedValue = computed(() => {
  const { value, format, formatOptions, prefix, suffix } = props.card

  let formatted = value

  switch (format) {
    case 'currency':
      formatted = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: formatOptions || 'USD',
      }).format(value)
      break
    case 'percentage':
      formatted = `${value}%`
      break
    case 'number':
    default:
      formatted = new Intl.NumberFormat().format(value)
  }

  if (prefix) formatted = `${prefix}${formatted}`
  if (suffix) formatted = `${formatted} ${suffix}`

  return formatted
})

const colorClasses = computed(() => {
  const colors = {
    blue: 'bg-blue-50 text-blue-600',
    green: 'bg-green-50 text-green-600',
    red: 'bg-red-50 text-red-600',
    yellow: 'bg-yellow-50 text-yellow-600',
    purple: 'bg-purple-50 text-purple-600',
    gray: 'bg-gray-50 text-gray-600',
  }
  return colors[props.card.color] || colors.blue
})

const trendColor = computed(() => {
  if (!props.card.trend) return ''
  return props.card.trend.startsWith('+') ? 'text-green-600' : 'text-red-600'
})
</script>

<template>
  <div class="value-card bg-white rounded-lg shadow p-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-gray-500">{{ card.name }}</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">
          {{ formattedValue }}
        </p>
        <p v-if="card.trend" :class="['text-sm mt-1', trendColor]">
          {{ card.trend }}
        </p>
      </div>
      <div
        v-if="card.icon"
        :class="['p-3 rounded-full', colorClasses]"
      >
        <Icon :name="card.icon" class="w-6 h-6" />
      </div>
    </div>

    <a
      v-if="card.link"
      :href="card.link"
      class="text-sm text-blue-600 hover:underline mt-2 inline-block"
    >
      View details →
    </a>
  </div>
</template>
```

### Trend Card Component

```vue
<!-- components/cards/TrendCard.vue -->
<script setup>
import { computed } from 'vue'
import Icon from '@/components/common/Icon.vue'

const props = defineProps({
  card: {
    type: Object,
    required: true,
  },
})

const trendIcon = computed(() => {
  return props.card.changeDirection === 'up' ? 'trending-up' : 'trending-down'
})

const trendColor = computed(() => {
  const isGood = props.card.increaseIsGood
    ? props.card.changeDirection === 'up'
    : props.card.changeDirection === 'down'

  return isGood ? 'text-green-600' : 'text-red-600'
})

const formattedValue = computed(() => {
  const { value, format, suffix } = props.card
  let formatted = format === 'currency'
    ? new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value)
    : new Intl.NumberFormat().format(value)

  if (suffix) formatted += ` ${suffix}`
  return formatted
})
</script>

<template>
  <div class="trend-card bg-white rounded-lg shadow p-4">
    <p class="text-sm text-gray-500">{{ card.name }}</p>

    <div class="flex items-center justify-between mt-2">
      <p class="text-2xl font-bold text-gray-900">
        {{ formattedValue }}
      </p>

      <div :class="['flex items-center gap-1', trendColor]">
        <Icon :name="trendIcon" class="w-5 h-5" />
        <span class="text-sm font-medium">
          {{ Math.abs(card.change) }}%
        </span>
      </div>
    </div>

    <p class="text-xs text-gray-400 mt-1">
      vs. {{ card.previousValue }} previous period
    </p>
  </div>
</template>
```

### Chart Card with Chart.js

```vue
<!-- components/cards/ChartCard.vue -->
<script setup>
import { ref, onMounted, watch } from 'vue'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js'
import { Line, Bar, Pie, Doughnut } from 'vue-chartjs'

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

const props = defineProps({
  card: {
    type: Object,
    required: true,
  },
})

const chartComponent = {
  line: Line,
  bar: Bar,
  pie: Pie,
  doughnut: Doughnut,
  donut: Doughnut,
}

const chartData = computed(() => {
  const { data, xAxis, yAxis, color, fill } = props.card

  return {
    labels: data.map(item => item[xAxis]),
    datasets: [
      {
        data: data.map(item => item[yAxis]),
        borderColor: color,
        backgroundColor: fill ? `${color}33` : 'transparent',
        fill: fill,
        tension: 0.3,
      },
    ],
  }
})

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false,
    },
  },
  scales: {
    x: {
      grid: {
        display: false,
      },
    },
    y: {
      beginAtZero: true,
    },
  },
}))
</script>

<template>
  <div class="chart-card bg-white rounded-lg shadow p-4">
    <p class="text-sm font-medium text-gray-700 mb-4">{{ card.name }}</p>

    <div :style="{ height: `${card.height}px` }">
      <component
        :is="chartComponent[card.chartType]"
        :data="chartData"
        :options="chartOptions"
      />
    </div>
  </div>
</template>
```

---

## Routes

```php
// Register in ServiceProvider
Route::prefix('api')
    ->middleware(['api', 'auth:sanctum'])
    ->group(function () {
        // Resource cards
        Route::get('/resources/{resource}/cards', [CardController::class, 'resource']);
        Route::get('/resources/{resource}/cards/{cardId}', [CardController::class, 'show']);

        // Dashboard widgets
        Route::get('/panels/{panel}/widgets', [CardController::class, 'dashboard']);
    });
```

---

## Integration with Resource Index

```vue
<!-- In ResourceManager.vue -->
<template>
  <div class="resource-manager">
    <!-- Cards at top of resource -->
    <CardGrid
      v-if="hasCards"
      :resource="resource"
      class="mb-6"
    />

    <!-- Rest of resource table/form -->
    <ResourceTable ... />
  </div>
</template>
```

---

## Implementation Checklist

### Backend
- [ ] Create Card base class
- [ ] Create ValueCard
- [ ] Create TrendCard
- [ ] Create PartitionCard
- [ ] Create TableCard
- [ ] Create ChartCard
- [ ] Create CustomCard
- [ ] Create CardService
- [ ] Create CardController
- [ ] Add routes
- [ ] Update config/studio.php for widgets
- [ ] Register in ServiceProvider

### Frontend
- [ ] Install Chart.js and vue-chartjs
- [ ] Create cardService.js
- [ ] Create CardGrid component
- [ ] Create CardWrapper component
- [ ] Create ValueCard component
- [ ] Create TrendCard component
- [ ] Create PartitionCard component
- [ ] Create TableCard component
- [ ] Create ChartCard component
- [ ] Create chart components (Line, Bar, Pie, Donut)
- [ ] Integrate with ResourceManager
- [ ] Create dashboard widget system

### Testing
- [ ] Unit tests for Card classes
- [ ] Unit tests for CardService
- [ ] Feature tests for CardController
- [ ] Frontend component tests
