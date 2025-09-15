# Actor Management System

A modern Laravel application for managing actor information submissions with AI-powered data extraction. Built with enterprise-grade architecture, Vue.js frontend, and comprehensive OpenAI integration.

## 🎬 **Live Demo**

Watch the application in action:

> **Demo Video**: [📹 View Full Demo](docs/demo/actor-management-demo.mp4)
> 
> See the complete workflow from actor submission to AI-powered data extraction and management.
> 
> *Note: After uploading to GitHub, you can replace this with an embedded video using GitHub's video upload feature in issues/PRs.*

## 🌟 **Features**

### **Core Functionality**
- 📝 **Actor Submission Form**: Intuitive form for submitting actor information
- 🤖 **AI-Powered Extraction**: Automatically extracts structured data from descriptions using OpenAI
- 📊 **Actor Management**: View, filter, and search through actor submissions
- 🔍 **Detailed Views**: Comprehensive actor profile pages
- 📈 **Real-time Statistics**: Live dashboard with submission metrics

### **Data Processing**
- **Extracts**: First Name, Last Name, Address, Height, Weight, Gender, Age
- **Validation**: Multi-layer validation with business rules
- **Error Handling**: Intelligent error messages for incomplete data
- **Retry Mechanism**: Automatic retry for failed processing

### **Modern UI/UX**
- 🎨 **Sheet-based Forms**: Modern overlay forms using Shadcn/ui
- 📱 **Responsive Design**: Works seamlessly on all devices
- ⚡ **Real-time Updates**: Live filtering and search capabilities
- 🎯 **Sample Data**: One-click form population for testing

## 🚀 **Quick Start**

### Prerequisites
- PHP 8.2+
- Composer
- Node.js & NPM
- OpenAI API Key (already configured)

### Installation
```bash
cd actor-management-system
composer install
npm install
npm run build
php artisan serve
```

The application will be available at `http://localhost:8000`

## 🎯 **Usage**

### **Getting Started**
1. **Submit Actor Information**: Click "Submit New Actor" button on the main page
2. **Browse Actors**: View the table with filtering and search capabilities
3. **Actor Details**: Click "View" on any actor to see complete information
4. **API Access**: Use the REST API for programmatic access

### **API Endpoints**

#### **Core Endpoints**
```http
GET /api/actors/prompt-validation    # Get prompt validation message
POST /actors                         # Submit actor information
GET /actors                          # Get paginated actors list
GET /actors/{uuid}                   # Get specific actor details
POST /actors/{uuid}/retry            # Retry failed processing
```

#### **Utility Endpoints**
```http
GET /api/health                      # API health check
GET /api/docs                        # API documentation
```

## 🏗️ **Architecture**

### **Backend Architecture**
- **Service Layer**: Business logic with dependency injection
- **Repository Pattern**: Data access abstraction with caching
- **DTOs**: Type-safe data handling throughout the application
- **Custom Validation**: Multi-layer validation with business rules
- **Event System**: Decoupled event handling for processing workflows

### **Frontend Architecture**
- **Vue.js Components**: Modern, reactive user interface
- **Shadcn/ui**: Professional, accessible component library
- **Responsive Design**: Mobile-first approach with Tailwind CSS
- **Real-time Updates**: Dynamic content updates without page refresh

### **AI Integration**
- **OpenAI API**: GPT-powered text analysis and data extraction
- **Circuit Breaker**: Resilient API calls with automatic failover
- **Retry Logic**: Intelligent retry mechanism for failed requests
- **Caching**: Response caching for improved performance

## 🧪 **Testing**

Comprehensive test suite included:

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
```

## 🔧 **Configuration**

### **Environment Setup**
The application comes pre-configured for development:
- **Database**: SQLite (no additional setup required)
- **OpenAI API**: Pre-configured API key
- **Caching**: File-based caching for development
- **Queue**: Sync driver for immediate processing

### **Production Considerations**
For production deployment, consider:
- **Database**: MySQL or PostgreSQL
- **Cache**: Redis for improved performance
- **Queue**: Redis or database queue driver
- **Environment**: Proper `.env` configuration

## 📋 **Project Structure**

```
actor-management-system/
├── app/
│   ├── Http/Controllers/ActorController.php    # Main controller
│   ├── Services/                               # Business logic
│   ├── Repositories/                           # Data access
│   ├── DTOs/                                   # Data transfer objects
│   └── Models/Actor.php                        # Actor model
├── resources/
│   ├── js/components/                          # Vue.js components
│   └── views/                                  # Blade templates
├── routes/
│   ├── web.php                                 # Web routes
│   └── api.php                                 # API routes
└── tests/                                      # Test suite
```

## 🛠️ **Technology Stack**

### **Backend**
- **Laravel 11**: Modern PHP framework with latest features
- **PHP 8.2+**: Latest PHP version with improved performance
- **SQLite**: Lightweight database for development
- **OpenAI API**: GPT-powered text analysis

### **Frontend**
- **Vue.js 3**: Progressive JavaScript framework
- **Shadcn/ui**: Modern component library
- **Tailwind CSS**: Utility-first CSS framework
- **Vite**: Fast build tool and dev server

### **Development Tools**
- **PHPUnit**: Comprehensive testing framework
- **Laravel Sail**: Docker development environment
- **Composer**: PHP dependency management
- **NPM**: Node.js package management

## 🤝 **Contributing**

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 **License**

This project is open source and available under the [MIT License](LICENSE).

---

**Built with ❤️ using Laravel, Vue.js, and modern web technologies.**
