# School Management API - System Architecture Map

## Current System Architecture

```mermaid
graph TB
    %% External Layer
    Client[Client Applications<br/>Frontend/Mobile Apps]
    
    %% API Gateway Layer
    subgraph "API Layer"
        CORS[CORS Middleware<br/>DebugCorsMiddleware]
        Auth[Authentication<br/>Sanctum Middleware]
        Routes[API Routes<br/>routes/api.php]
    end
    
    %% Controller Layer
    subgraph "Controller Layer"
        BaseController[Base Controller<br/>Response Helpers]
        AuthController[Auth Controller<br/>Login/Register/Logout]
        DashboardController[Dashboard Controller<br/>Admin Dashboard]
        
        subgraph "Resource Controllers"
            TeacherController[Teacher Controller<br/>CRUD Operations]
            StudentController[Student Controller<br/>CRUD Operations]
            ParentController[Parent Controller<br/>CRUD Operations]
            ClassController[School Class Controller<br/>Class Management]
            AttendanceController[Attendance Controller<br/>Attendance Tracking]
        end
    end
    
    %% Business Logic Layer
    subgraph "Model Layer"
        UserModel[User Model<br/>Base User Entity]
        TeacherModel[Teacher Model<br/>Teacher-specific Data]
        StudentModel[Student Model<br/>Student-specific Data]
        ParentModel[Parent Model<br/>Parent-specific Data]
        ClassModel[School Class Model<br/>Class Management]
        AttendanceModel[Attendance Model<br/>Attendance Records]
    end
    
    %% Data Layer
    subgraph "Database Layer"
        MySQL[(MySQL Database)]
        
        subgraph "Core Tables"
            UsersTable[users]
            TeachersTable[teachers]
            StudentsTable[students]
            ParentsTable[parents]
            ClassesTable[classes]
            AttendanceTable[attendances]
            ClassStudentTable[class_student<br/>Pivot Table]
        end
    end
    
    %% Authentication & Security
    subgraph "Security Layer"
        Sanctum[Laravel Sanctum<br/>API Token Authentication]
        Policies[Authorization Policies<br/>Access Control]
        Validation[Request Validation<br/>Form Requests]
    end
    
    %% Infrastructure
    subgraph "Infrastructure"
        Laravel[Laravel 11 Framework]
        Composer[Composer Dependencies]
        Heroku[Heroku Deployment<br/>CI/CD Pipeline]
    end
    
    %% Connections
    Client --> CORS
    CORS --> Routes
    Routes --> Auth
    Auth --> BaseController
    
    BaseController --> AuthController
    BaseController --> DashboardController
    BaseController --> TeacherController
    BaseController --> StudentController
    BaseController --> ParentController
    BaseController --> ClassController
    BaseController --> AttendanceController
    
    TeacherController --> TeacherModel
    StudentController --> StudentModel
    ParentController --> ParentModel
    ClassController --> ClassModel
    AttendanceController --> AttendanceModel
    AuthController --> UserModel
    
    UserModel --> UsersTable
    TeacherModel --> TeachersTable
    StudentModel --> StudentsTable
    ParentModel --> ParentsTable
    ClassModel --> ClassesTable
    AttendanceModel --> AttendanceTable
    
    TeacherModel --> UsersTable
    StudentModel --> UsersTable
    ParentModel --> UsersTable
    
    StudentsTable --> ClassStudentTable
    ClassesTable --> ClassStudentTable
    
    Auth --> Sanctum
    BaseController --> Policies
    BaseController --> Validation
    
    MySQL --> Laravel
    Laravel --> Composer
    Laravel --> Heroku
```

## Database Relationship Diagram

```mermaid
erDiagram
    USERS {
        bigint id PK
        string name
        string email UK
        timestamp email_verified_at
        string password
        enum role "admin,teacher,student,parent"
        timestamps created_at_updated_at
    }
    
    TEACHERS {
        bigint id PK
        bigint user_id FK
        string employee_id UK
        string phone
        enum gender "male,female,other"
        text address
        date date_of_birth
        string qualification
        decimal salary
        date hire_date
        timestamps created_at_updated_at
    }
    
    STUDENTS {
        bigint id PK
        bigint user_id FK
        string student_id UK
        bigint parent_id FK
        string phone
        enum gender "male,female,other"
        text address
        date date_of_birth
        timestamps created_at_updated_at
    }
    
    PARENTS {
        bigint id PK
        bigint user_id FK
        string phone
        enum gender "male,female,other"
        text address
        string occupation
        timestamps created_at_updated_at
    }
    
    CLASSES {
        bigint id PK
        string name
        string description
        bigint teacher_id FK
        timestamps created_at_updated_at
    }
    
    CLASS_STUDENT {
        bigint id PK
        bigint class_id FK
        bigint student_id FK
        timestamps created_at_updated_at
    }
    
    ATTENDANCES {
        bigint id PK
        bigint student_id FK
        bigint class_id FK
        date date
        enum status "present,absent,late"
        text remarks
        timestamps created_at_updated_at
    }
    
    PERSONAL_ACCESS_TOKENS {
        bigint id PK
        string tokenable_type
        bigint tokenable_id
        string name
        string token UK
        text abilities
        timestamp last_used_at
        timestamp expires_at
        timestamps created_at_updated_at
    }
    
    %% Relationships
    USERS ||--|| TEACHERS : "user_id"
    USERS ||--|| STUDENTS : "user_id"
    USERS ||--|| PARENTS : "user_id"
    
    PARENTS ||--o{ STUDENTS : "parent_id"
    TEACHERS ||--o{ CLASSES : "teacher_id"
    
    CLASSES ||--o{ CLASS_STUDENT : "class_id"
    STUDENTS ||--o{ CLASS_STUDENT : "student_id"
    
    STUDENTS ||--o{ ATTENDANCES : "student_id"
    CLASSES ||--o{ ATTENDANCES : "class_id"
    
    USERS ||--o{ PERSONAL_ACCESS_TOKENS : "tokenable_id"
```

## API Endpoint Structure

```mermaid
graph LR
    subgraph "Public Endpoints"
        Login["POST /api/login"]
        Register["POST /api/register"]
        TeacherDropdown["GET /api/teachers/dropdown-options"]
        StudentDropdown["GET /api/students/dropdown-options"]
        CorsTest["GET /api/test-cors"]
    end
    
    subgraph "Protected Endpoints"
        Logout["POST /api/logout"]
        Dashboard["GET /api/admin/dashboard"]
        
        subgraph "Teacher Management"
            TeacherIndex["GET /api/teachers"]
            TeacherStore["POST /api/teachers"]
            TeacherShow["GET /api/teachers/(id)"]
            TeacherUpdate["PUT/PATCH /api/teachers/(id)"]
            TeacherDestroy["DELETE /api/teachers/(id)"]
        end
        
        subgraph "Student Management"
            StudentIndex["GET /api/students"]
            StudentStore["POST /api/students"]
            StudentShow["GET /api/students/(id)"]
            StudentUpdate["PUT/PATCH /api/students/(id)"]
            StudentDestroy["DELETE /api/students/(id)"]
        end
        
        subgraph "Parent Management"
            ParentIndex["GET /api/parents"]
            ParentStore["POST /api/parents"]
            ParentShow["GET /api/parents/(id)"]
            ParentUpdate["PUT/PATCH /api/parents/(id)"]
            ParentDestroy["DELETE /api/parents/(id)"]
        end
        
        subgraph "Class & Attendance"
            ClassStore["POST /api/classes"]
            AttendanceMark["POST /api/attendance"]
        end
    end
    
    Auth["auth:sanctum middleware"] --> Dashboard
    Auth --> TeacherIndex
    Auth --> StudentIndex
    Auth --> ParentIndex
    Auth --> ClassStore
    Auth --> AttendanceMark
```

## Current System Status

### ✅ Implemented Features
- **Authentication System**: Laravel Sanctum with role-based access
- **User Management**: Multi-role user system (admin, teacher, student, parent)
- **CRUD Operations**: Complete resource management for Teachers, Students, Parents
- **Database Relations**: Proper foreign key relationships and pivot tables
- **API Documentation**: Swagger/OpenAPI integration
- **Error Handling**: Structured JSON responses with global exception handling
- **CORS Support**: Cross-origin resource sharing middleware
- **Validation**: Comprehensive form validation with custom messages
- **Route Model Binding**: Automatic model resolution in controllers

### ⚠️ Partially Implemented
- **Class Management**: Basic class creation, needs full CRUD
- **Attendance System**: Basic attendance marking, needs reporting features
- **Dashboard**: Basic admin dashboard, needs comprehensive analytics

### ❌ Missing Features
- **Student Portal**: Dedicated student authentication and dashboard
- **Parent Portal**: Parent-specific features and child monitoring
- **Teacher Portal**: Teacher-specific dashboard and class management
- **Reporting System**: Academic reports, attendance reports, performance analytics
- **Notification System**: Email/SMS notifications for parents and students
- **Grade Management**: Subject grades, report cards, academic performance
- **Timetable Management**: Class schedules and time management
- **Fee Management**: Fee collection, payment tracking
- **Library Management**: Book inventory, borrowing system
- **Exam Management**: Exam scheduling, results management

## Technology Stack

### Backend
- **Framework**: Laravel 11
- **Authentication**: Laravel Sanctum
- **Database**: MySQL
- **API Documentation**: Swagger/OpenAPI
- **Validation**: Laravel Form Requests

### DevOps & Deployment
- **Version Control**: Git
- **Dependency Management**: Composer
- **Deployment**: Heroku with automated CI/CD
- **Environment**: XAMPP (Development)

### Frontend (Not Implemented)
- **Recommended**: React.js/Vue.js/Angular
- **Mobile**: React Native/Flutter (Future consideration)
