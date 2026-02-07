# Complete AI Website Builder System
## All-In-One Document for AI-Powered Website Generation

**Purpose**: Upload this single file to AI to generate a complete, production-ready Laravel website.

**How it works**:
1. You upload this file to AI (Claude, ChatGPT, etc.)
2. You describe your website idea
3. AI generates a detailed site structure for you
4. You use that structure to generate the complete website code

---

# TABLE OF CONTENTS

1. [SYSTEM OVERVIEW](#system-overview)
2. [USAGE INSTRUCTIONS](#usage-instructions)
3. [MAIN WEBSITE BUILDER PROMPT](#main-website-builder-prompt)
4. [SITE STRUCTURE GENERATOR PROMPT](#site-structure-generator-prompt)
5. [BLANK TEMPLATE](#blank-template)
6. [EXAMPLE 1: CREATIVE AGENCY WEBSITE](#example-1-creative-agency-website)
7. [EXAMPLE 2: GITHUB PACKAGE DOCUMENTATION](#example-2-github-package-documentation)
8. [QUICK REFERENCE](#quick-reference)

---

<a name="system-overview"></a>
# 1. SYSTEM OVERVIEW

## What This System Does

This is a comprehensive prompt engineering system that generates complete, production-ready Laravel websites with:

### Backend
- **Laravel 11** - Latest stable PHP framework
- **MySQL 8.0+** - Database
- **Redis** - Caching layer
- **Docker** - Containerization
- Clean architecture with Repository pattern

### Frontend
- **Vue 3** - JavaScript framework (Composition API)
- **Tailwind CSS** - Utility-first styling
- **Vite** - Modern build tool
- **Lenis** - Smooth scrolling
- Single Page Application (SPA)

### Features
- ✅ Admin panel with authentication
- ✅ Full CRUD for all content
- ✅ Light/Dark theme switching
- ✅ Redis caching with manual rebuild
- ✅ SEO optimization (meta tags, structured data)
- ✅ Mobile-responsive design
- ✅ Accessibility (WCAG 2.1 AA)
- ✅ Cookie consent (GDPR compliant)
- ✅ Docker deployment ready

## Supported Website Types

1. **Portfolio** - Developer/designer showcases
2. **Business/Agency** - Service companies
3. **Restaurant** - Menus and reservations
4. **E-commerce** - Product catalogs
5. **Blog/Magazine** - Content sites
6. **SaaS Landing** - Product pages
7. **Real Estate** - Property listings
8. **Package Documentation** - GitHub repos, NPM/Composer packages

---

<a name="usage-instructions"></a>
# 2. USAGE INSTRUCTIONS

## Two-Step Process

### Step 1: Generate Site Structure

**What you provide**:
- This complete document (already uploaded)
- Your website description (2-3 paragraphs)

**What AI generates**:
- Complete, detailed site structure document
- All sections defined
- All data models specified
- Validation rules included
- Design preferences set
- Ready to use for code generation

### Step 2: Generate Website Code

**What you provide**:
- The Main Website Builder Prompt (Section 3 below)
- The generated site structure from Step 1

**What AI generates**:
- Complete Laravel backend
- Complete Vue.js frontend
- Admin panel
- Docker configuration
- All necessary files
- README with setup instructions

## How to Use This Document

### Option A: Let AI Generate Structure (Recommended)

**Paste this to AI**:

```
I want to build a website. Here's the complete AI Website Builder system.

[Paste Section 4: SITE STRUCTURE GENERATOR PROMPT below]

---

BLANK TEMPLATE:
[Paste Section 5: BLANK TEMPLATE below]

---

EXAMPLES FOR REFERENCE:
[Paste Section 6 AND/OR Section 7 below depending on your site type]

---

MY WEBSITE IDEA:

[Describe your website in 2-3 paragraphs with details about:
- What type of website it is
- What sections it needs
- What features you want
- Who the target audience is
- Design preferences]

---

Please generate a complete site structure document by filling out the blank template.
Use the examples as reference for the level of detail needed.
```

**After receiving the structure, proceed to Step 2**:

```
Build a complete Laravel website.

[Paste Section 3: MAIN WEBSITE BUILDER PROMPT below]

---

MY SITE STRUCTURE:
[Paste the generated structure from Step 1]

---

Please generate all files for this website.
```

### Option B: Fill Template Manually

1. Copy Section 5 (BLANK TEMPLATE) to a new document
2. Fill it out yourself (use Section 6 or 7 as reference)
3. Use your filled structure with Section 3 to generate code

---

<a name="main-website-builder-prompt"></a>
# 3. MAIN WEBSITE BUILDER PROMPT

## System Prompt

You are an expert full-stack developer specializing in Laravel, Vue.js, and modern web development. You will build a complete, production-ready website based on the provided site structure document.

---

## Project Requirements

### Technology Stack
- **Backend**: Laravel 11 (latest stable)
- **Frontend**: Vue 3 with Composition API
- **Styling**: Tailwind CSS with custom design system
- **Database**: MySQL 8.0+
- **Cache**: Redis for application caching
- **Build Tool**: Vite
- **Containerization**: Docker with docker-compose

### Architecture Patterns
1. **Single Page Application (SPA)**: Vue.js frontend consuming Laravel API
2. **RESTful API**: Clean, versioned API endpoints (`/api/v1/*`)
3. **Repository Pattern**: For database abstraction
4. **Service Layer**: Business logic separated from controllers
5. **Cache Strategy**: Redis caching with manual invalidation from admin
6. **SEO-First**: Server-side meta tags, structured data, proper semantic HTML

---

## Core Features Required

### 1. Frontend (Public Site)

#### Design System
- Custom CSS variables for theming (light/dark modes)
- Glass morphism effects with backdrop blur
- Smooth animations using CSS transitions and keyframes
- Responsive design (mobile-first approach)
- Accessible components (ARIA labels, semantic HTML)

#### Theme Switching
- Toggle between light and dark themes
- Smooth zoom-blur transition animation
- Cookie-based persistence (with consent)
- No flash on page load
- Theme preference saved in cookies after consent

#### Smooth Scrolling
- Lenis smooth scroll library integration
- Anchor-based navigation with smooth scroll to sections
- Scroll progress indicator
- Active section highlighting in navigation

#### Animations
- Intersection Observer for scroll-triggered animations
- Cursor glow effect following mouse movement
- Floating background elements
- Hover effects on interactive elements

#### Cookie Consent
- GDPR-compliant cookie banner
- Accept/Reject functionality
- localStorage for consent status
- Theme preference only saved with consent

### 2. Backend (Admin Panel)

#### Authentication
- Laravel Breeze or similar for admin auth
- Secure login with CSRF protection
- Password reset functionality

#### Content Management
- CRUD operations for all content sections
- Rich text editor for long-form content (TinyMCE or similar)
- Image upload and management
- Drag-and-drop ordering for lists
- Visibility toggles (show/hide items)
- SEO fields for each content type

#### File Upload (if required by site structure)
- Drag-and-drop upload zone
- Image gallery table with:
  - Thumbnail preview
  - Public URL with copy button
  - Caption/description editing
  - Delete functionality
  - File size and dimensions display
- Storage in Laravel storage folder (`storage/app/public/`)
- Auto-optimization and thumbnail generation

#### Settings Management
- Site-wide settings (stored in JSON in database)
- Individual section settings
- Social links management
- Footer text configuration

#### Cache Management
- "Rebuild Cache" button in admin
- Automatic cache invalidation on content updates
- Display cache status

#### Validation
- Server-side validation for all inputs
- Form requests for complex validation
- Proper error messages
- XSS protection

### 3. SEO Optimization

#### Meta Tags
- Dynamic title, description, keywords
- Open Graph tags (og:*)
- Twitter Card tags
- Canonical URLs

#### Structured Data (JSON-LD)
- Person schema (if applicable)
- WebSite schema
- Organization/ProfessionalService schema
- BreadcrumbList for navigation
- SoftwareApplication schema (for package docs)

#### Technical SEO
- Proper semantic HTML5 structure
- robots.txt with API routes blocked
- Favicon with multiple sizes (48x48, 96x96)
- Accessible images with alt text
- Clean URLs
- Fast page load (optimized assets)
- Mobile-responsive

#### Performance
- Redis caching for all API responses
- Vite asset bundling and minification
- Lazy loading for images
- Optimized database queries (eager loading)

### 4. Database Design

#### Models with Relationships
- Eloquent models for all content types
- Scopes for common queries (visible, ordered)
- Proper foreign keys and indexes

#### Migrations
- Clean migration files
- Proper column types and constraints
- Indexes on frequently queried columns

#### Seeders
- DatabaseSeeder with sample content
- Realistic placeholder data for testing

### 5. API Design

#### Endpoints
- Single endpoint for fetching all data (`GET /api/v1/portfolio` or similar)
- Rate limiting (60 requests per minute)
- JSON responses with consistent structure
- Proper HTTP status codes

#### Response Format
```json
{
  "success": true,
  "data": {
    // content here
  }
}
```

### 6. Docker Setup

#### Services
- `app`: PHP 8.4-fpm with Laravel
- `mysql`: MySQL 8.0
- `redis`: Redis for caching

#### Configuration
- Multi-stage Dockerfile for production
- Composer and npm install in build
- Proper volume mounts
- Environment variables via .env
- Health checks for services

---

## UI/UX Requirements

### Design Principles
1. **Minimalist**: Clean, uncluttered interface
2. **Modern**: Glass morphism, subtle shadows, smooth animations
3. **Readable**: High contrast, proper font sizes, line spacing
4. **Professional**: Polished details, consistent spacing
5. **Accessible**: WCAG 2.1 AA compliance

### Color Scheme

**Dark Theme** (default):
- Background: Very dark (#0a0a0b)
- Text: Near white (#fafafa)
- Accent: Vibrant color (from site structure)
- Borders: Subtle semi-transparent

**Light Theme**:
- Background: Light gray (#f5f5f7)
- Text: Very dark (#1a1a2e)
- Accent: Bold color (from site structure)
- Borders: Subtle dark

### Typography
- Sans-serif for body text (modern, readable)
- Monospace for labels/tags
- Proper font weights (light, normal, medium, bold)
- Clear heading hierarchy (h1-h6)
- Consistent sizes and weights
- Proper line heights

### Spacing
- Consistent spacing scale (4px, 8px, 16px, 24px, 32px, etc.)
- Generous whitespace between sections
- Proper padding in cards and containers

---

## Code Quality Standards

### Laravel Backend
- PSR-12 coding standards
- Type hints for all methods
- DocBlocks for complex logic
- Service classes for business logic
- Form Request validation
- Resource classes for API responses (if needed)
- Repository pattern for data access

### Vue.js Frontend
- Composition API with `<script setup>`
- TypeScript-style JSDoc comments
- Component-based architecture
- Props validation
- Emits declaration
- Scoped styles
- Accessibility attributes (ARIA labels, roles, semantic HTML)

### General
- No hardcoded values (use config, env, database)
- DRY principle (Don't Repeat Yourself)
- SOLID principles
- Secure by default (CSRF, XSS protection)
- Error handling
- Logging for important operations

---

## File Structure

```
project-root/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AdminController.php
│   │   │   └── ApiController.php
│   │   ├── Requests/
│   │   └── Middleware/
│   ├── Models/
│   │   ├── SiteSettings.php
│   │   └── [ContentModels].php
│   └── Services/
├── config/
│   ├── cache.php
│   └── database.php
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   ├── css/
│   │   └── app.css (Tailwind + custom styles)
│   ├── js/
│   │   ├── app.js
│   │   ├── App.vue
│   │   └── components/
│   │       ├── NavBar.vue
│   │       ├── [SectionComponents].vue
│   │       └── CookieBanner.vue
│   └── views/
│       ├── app.blade.php (main SPA entry)
│       └── admin/
│           └── dashboard.blade.php
├── routes/
│   ├── web.php
│   ├── api.php
│   └── auth.php
├── public/
│   ├── favicon.ico
│   ├── robots.txt
│   └── images/
├── storage/
│   └── app/
│       └── public/
│           └── media/ (for file uploads if needed)
├── docker/
│   └── entrypoint.sh
├── Dockerfile
├── docker-compose.yml
├── .env.example
├── .gitignore
└── README.md
```

---

## Implementation Instructions

### Step 1: Setup Laravel Project
1. Create fresh Laravel 11 installation structure
2. Install required packages:
   ```bash
   composer require laravel/breeze --dev
   npm install vue@next lenis axios
   ```
3. Configure environment (.env.example)
4. Setup Docker environment

### Step 2: Database Schema
1. Create migrations based on site structure
2. Create models with relationships, scopes, and attributes
3. Add accessors and mutators as needed
4. Create seeders with sample data

### Step 3: Backend API
1. Create API controller with single endpoint (or multiple if specified)
2. Implement Redis caching strategy (`Cache::rememberForever()`)
3. Setup rate limiting
4. Create admin controllers with CRUD operations
5. Implement form validation using Form Requests

### Step 4: Admin Panel
1. Setup authentication (Laravel Breeze)
2. Create admin dashboard views
3. Implement content management forms
4. Add image upload handling (if required)
5. Add cache rebuild functionality
6. Create drag-and-drop ordering (if required)

### Step 5: Frontend
1. Setup Vue 3 with Vite
2. Create component structure based on sections
3. Implement API calls with axios
4. Add smooth scroll (Lenis)
5. Implement theme switching with zoom-blur transition
6. Add animations and effects (Intersection Observer, cursor glow)
7. Create cookie consent banner

### Step 6: SEO Optimization
1. Add comprehensive meta tags to blade template
2. Implement JSON-LD structured data
3. Create robots.txt
4. Setup favicons (multiple sizes)
5. Optimize images
6. Add accessibility attributes throughout

### Step 7: Docker & Deployment
1. Create production-ready Dockerfile
2. Setup docker-compose.yml
3. Configure services (MySQL, Redis)
4. Create entrypoint script
5. Test build process

### Step 8: Special Features

If site structure requires:

#### Markdown Rendering
- Store markdown in database
- Use marked.js or markdown-it for parsing
- Add Prism.js for syntax highlighting
- Support GitHub Flavored Markdown
- Implement copy buttons on code blocks

#### File Upload System
- Create upload endpoint with validation
- Store files in `storage/app/public/media/`
- Generate thumbnails (300x300)
- Optimize images on upload
- Create gallery table in admin with:
  - Preview column (thumbnail)
  - URL column with copy button
  - Actions (view, copy, delete)
  - Sortable and searchable

---

## Expected Deliverables

When using this prompt, generate:

✅ Complete Laravel 11 project structure
✅ Vue 3 SPA with all components
✅ MySQL database with migrations and seeders
✅ Redis caching implementation
✅ Admin panel with authentication
✅ SEO-optimized with meta tags and structured data
✅ Light/dark theme with smooth transitions
✅ Fully responsive design
✅ Docker setup for easy deployment
✅ .env.example with all variables
✅ .gitignore with proper exclusions
✅ README.md with setup instructions
✅ Clean, documented code
✅ All features from site structure implemented

---

## Important Notes

- Follow the site structure document exactly
- Implement ALL sections and features specified
- Use the exact color schemes provided
- Include all validation rules specified
- Implement all data models with proper relationships
- Create admin interface for ALL content types
- Don't add features not in the structure
- Don't skip any specified requirements
- Ensure mobile responsiveness for all components
- Test all CRUD operations
- Verify cache invalidation works
- Check SEO meta tags are correct

---

<a name="site-structure-generator-prompt"></a>
# 4. SITE STRUCTURE GENERATOR PROMPT

## Instructions for AI

You are a website planning expert. Your task is to help users create a detailed, comprehensive site structure document by filling out the provided blank template based on their website idea.

You will receive:
1. **Blank Template** - Empty site structure template to fill out
2. **Example Structure(s)** - Complete examples showing the level of detail required
3. **User's Site Description** - What kind of website they want to build

Your goal: Fill out the blank template with the same level of detail and completeness as the examples, but customized for the user's specific website type and requirements.

---

## Process

### Step 1: Understand the User's Requirements

Analyze the user's description carefully. If unclear, note what additional information would be helpful, but proceed with reasonable assumptions based on the website type.

Consider:
- Primary purpose of the website
- Target audience
- Must-have features
- Design preferences
- Third-party integrations needed

### Step 2: Plan the Structure

Based on the user's description, determine:
- What sections the site needs (minimum 5-8 sections)
- What content models are required
- What admin functionality is needed
- What forms/interactions are required
- What integrations are necessary

### Step 3: Fill the Template

Using the Example Structure(s) as your guide for **level of detail**, fill out every section of the blank template:

**Be Specific**:
- Don't just say "contact form", specify exact fields with types and validation
- Include example content to show what data looks like
- Describe mobile vs desktop behavior

**Be Comprehensive**:
- Include ALL necessary data models with ALL fields
- Define every relationship between models
- Specify ordering and visibility flags

**Be Detailed**:
- Describe layout and positioning
- Explain user interactions
- Include hover states and animations

**Be Realistic**:
- Use practical field names
- Include common constraints (max length, required, etc.)
- Think about actual use cases

**Be Complete**:
- Don't skip any sections of the template
- Fill in design preferences with colors
- Include success criteria

### Step 4: Match the Quality

Your output should have:
- Same level of detail as the examples (not more, not less)
- Clear section purposes
- Specific field definitions with types and constraints
- Validation rules for all inputs
- User flow descriptions
- Design preferences with exact colors
- Success criteria that are measurable

---

## Key Principles

### 1. Content First
Think about what content the site needs to display, then design models around it.

**Example**: Restaurant menu site needs:
- Menu items (name, description, price, category, allergens, image)
- Categories (name, description, order, is_visible)
- Special dietary tags (vegetarian, gluten-free, vegan)

### 2. Admin Experience
Every piece of content should be manageable from admin:
- What fields does admin need to edit?
- What should be drag-and-drop orderable?
- What needs bulk actions?
- What needs visibility toggles?
- What needs image upload?

### 3. User Experience
Describe how users interact:
- First impression (hero section)
- Information gathering (about, services, features)
- Trust building (testimonials, portfolio, case studies)
- Action taking (contact, signup, purchase)

### 4. Data Models
For each content type, specify:
- Model name (singular, PascalCase)
- All fields with types:
  - string (for short text, specify max length)
  - text (for long text)
  - integer (for numbers, counts, order)
  - boolean (for flags like is_visible)
  - date/datetime (for timestamps)
  - json (for arrays of structured data)
  - enum (for fixed choices)
- Constraints (required, unique, max length, nullable, default values)
- Relationships (belongsTo, hasMany, belongsToMany)
- Special attributes (is_visible, order, is_featured, is_published)

### 5. Validation Rules
Be specific about validation:
- Email: required, valid email format, max 255 characters
- Phone: optional, format (XXX) XXX-XXXX or international
- Message: required, minimum 20 characters, maximum 1000 characters
- Age: required, integer, between 18 and 100
- File: required, image (jpg,png), max 5MB

### 6. Design Details
Describe visual preferences:
- Color scheme (with hex codes for both light and dark themes)
- Typography choices (specific font names from Google Fonts)
- Layout patterns (grid, cards, full-width, sidebar)
- Style inspiration (reference real websites)
- Interactive elements (hover effects, transitions, animations)

---

## Common Website Types

### Portfolio Website
**Typical Sections**: Hero, About, Skills, Portfolio/Projects, Testimonials, Blog, Contact
**Key Models**: Project, Skill, Testimonial, BlogPost, Category
**Focus**: Showcasing work, establishing credibility

### Business/Agency Website
**Typical Sections**: Hero, Services, Portfolio, Team, Testimonials, Blog, Contact
**Key Models**: Service, Project, TeamMember, Testimonial, BlogPost
**Focus**: Selling services, building trust

### E-commerce Website
**Typical Sections**: Hero, Featured Products, Categories, Product Listings, Cart, Checkout
**Key Models**: Product, Category, Order, OrderItem, Customer
**Focus**: Product discovery, easy purchasing

### Restaurant Website
**Typical Sections**: Hero, Menu, Gallery, About, Reservations, Contact
**Key Models**: MenuItem, MenuCategory, Reservation, GalleryImage
**Focus**: Menu display, reservation booking

### Blog/Magazine
**Typical Sections**: Featured Posts, Category Archives, Article Pages, Author Profiles, Newsletter
**Key Models**: Article, Category, Author, Tag, Comment
**Focus**: Content discovery, reading experience

### SaaS Landing Page
**Typical Sections**: Hero, Features, Pricing, Testimonials, FAQ, Signup
**Key Models**: PricingPlan, Feature, Testimonial, FAQItem
**Focus**: Product explanation, conversion

### Real Estate Website
**Typical Sections**: Property Search, Featured Listings, Property Details, Agents, Contact
**Key Models**: Property, Agent, PropertyImage, Inquiry
**Focus**: Property browsing, inquiry generation

### GitHub Repository/Package Documentation Site
**Typical Sections**: Hero with project title, Quick Start/Installation, Features, Documentation (Markdown), Screenshots/Media Gallery, Useful Links, Download/GitHub Link
**Key Models**: ProjectInfo, UsefulLink, MediaFile, MarkdownContent
**Focus**: Clear documentation, easy onboarding, visual examples

**Special Features**:
- **Markdown Rendering**: Backend stores markdown text, frontend renders it properly with syntax highlighting (Prism.js/Highlight.js)
- **Media Management**: File upload system with image preview, accessible links, and delete functionality
- **Storage**: Images stored in Laravel storage folder (storage/app/public/media)
- **Admin Interface**:
  - Rich markdown editor for documentation content
  - File upload dropzone for images
  - Image gallery table showing: thumbnail preview, public URL with copy button, caption field, file info, delete action
  - Useful links manager (add/edit/delete/reorder)

**Typical Data Models**:
- **ProjectInfo**: name, tagline, description, github_url, documentation_url, version, license, installation_command
- **UsefulLink**: title, url, icon, description, category, order, is_visible
- **MediaFile**: filename, path, public_url, type (screenshot/diagram/demo/other), caption, file_size, mime_type, width, height, uploaded_at
- **MarkdownContent**: section_name, markdown_text, order, is_visible

**Example Use Cases**:
- Laravel package documentation
- Open source project introduction
- NPM package landing page
- Python library documentation
- WordPress theme/plugin page

---

## Quality Checklist

Before submitting, ensure your filled structure has:

- [ ] Site type and description clearly defined
- [ ] Minimum 5-8 sections (more for complex sites)
- [ ] Each section has: Purpose, Content, Admin/Model, Display
- [ ] All data models listed with ALL fields and types
- [ ] Relationships between models defined
- [ ] Validation rules for every form field
- [ ] Design preferences with colors (hex codes) and fonts
- [ ] User flow with 5+ steps
- [ ] Admin flow with 3+ steps
- [ ] Success criteria with 8+ measurable items
- [ ] Third-party integrations (if applicable)
- [ ] Same level of detail as the example

---

## Output Format

Generate a complete markdown document that:
1. Follows the exact structure of the blank template
2. Has the same section organization
3. Matches the detail level of the examples
4. Is customized for the user's specific website
5. Is ready to use with the Main Website Builder Prompt

---

<a name="blank-template"></a>
# 5. BLANK TEMPLATE

Copy this template and fill it out for your website:

```markdown
# Site Structure

## Site Type
[e.g., Portfolio, Business Website, E-commerce, Blog, Agency Site, Package Documentation, etc.]

## Site Description
[Brief overview of the website purpose, target audience, and main goals. 2-3 sentences.]

---

## Sections Required

### 1. [Section Name]
**Purpose**: [What is this section for?]

**Content**:
- [List what content this section displays]
- [Format, layout, interactive elements]

**Admin Fields** (if using site_settings):
- field_name (type, constraints)
- field_name (type, constraints)

**Data Model** (if needed): `ModelName`
- field_name (type, constraints)
- field_name (type, constraints)
- relationships
- ordering/visibility

**Display**: [How it looks on desktop vs mobile]

---

### 2. [Section Name]
[Repeat for each section - minimum 5-8 sections]

---

## Content Models Summary

List all database models needed:

1. **ModelName** - Description
   - field: type (constraints)
   - field: type (constraints)
   - Relationships: [describe]
   - Special features: [ordering, visibility, etc.]

2. **ModelName** - Description
   [Continue for all models]

---

## Specific Requirements

### Feature 1: [Name]
- Requirement details
- Functionality description
- User interaction flow

### Feature 2: [Name]
[Continue for all special features]

### Special Validations
- Field validation rules
- Business logic rules
- Security requirements

---

## Design Preferences

### Color Scheme
**Dark Theme** (default):
- Primary: [color name] (#hex code)
- Accent: [color name] (#hex code)
- Background: [color name] (#hex code)
- Text: [color name] (#hex code)

**Light Theme**:
- Primary: [color name] (#hex code)
- Accent: [color name] (#hex code)
- Background: [color name] (#hex code)
- Text: [color name] (#hex code)

### Typography
- Headings: [Font name and style, e.g., "Inter Bold"]
- Body: [Font name and style, e.g., "Inter Regular"]
- Code/Special: [Font name, e.g., "JetBrains Mono"]

### Style Inspiration
- [Website 1] - [What aspect to emulate]
- [Website 2] - [What aspect to emulate]

### Visual Elements
- [List design elements: gradients, shadows, animations, glass effects, etc.]
- [Layout preferences: cards, full-width, grid, etc.]
- [Interactive element styles: hover effects, transitions]

---

## Third-Party Integrations

List any external services needed:

- **Service Name**: Purpose and integration details
- **Service Name**: Purpose and integration details

---

## Admin Panel Requirements

### Dashboard
[What should appear on the admin dashboard?]

### Content Sections
[List all admin sections needed for managing content]

### Special Admin Features
[Any unique admin functionality like drag-drop, bulk actions, file uploads]

---

## Validation Rules

### [Form/Model Name]
- field: validation rules
- field: validation rules

### [Form/Model Name]
- field: validation rules
- field: validation rules

---

## Expected User Flow

### Visitor Journey
1. [Step 1]
2. [Step 2]
3. [Step 3]
[Continue with expected user actions through the site]

### Admin Journey
1. [Step 1]
2. [Step 2]
[Continue with admin workflow]

---

## Success Criteria

The website is successful if:
- ✅ [Criterion 1 - measurable]
- ✅ [Criterion 2 - measurable]
- ✅ [Criterion 3 - measurable]
[Continue - minimum 8 criteria]

---

## Additional Notes

[Any other important information, constraints, or special requirements]

---

**Site Name**: [Your site name]
**Target Audience**: [Who will use this site?]
**Tone**: [Professional, casual, playful, corporate, technical, friendly, etc.]
**Launch Timeline**: [Target completion date if applicable]
```

---

<a name="example-1-creative-agency-website"></a>
# 6. EXAMPLE 1: CREATIVE AGENCY WEBSITE

[Note: Use this example as reference for business/agency/service websites]

## Site Type
Modern creative agency website showcasing services, portfolio work, team members, and client testimonials.

## Site Description
A professional agency website that highlights our creative services, displays our best work, introduces our team, and builds trust through client testimonials. The site should feel modern, dynamic, and professional.

---

## Sections Required

### 1. Hero Section
**Purpose**: First impression with strong messaging and call-to-action

**Content**:
- Main headline (e.g., "We Create Digital Experiences")
- Subheading (brief description of agency)
- Primary CTA button (e.g., "Start Your Project")
- Secondary CTA button (e.g., "View Our Work")
- Background video or animated gradient

**Admin Fields** (in site_settings):
- headline (string, required, max 100 chars)
- subheading (text, max 200 chars)
- primary_cta_text (string, max 50 chars)
- primary_cta_link (string, URL format)
- secondary_cta_text (string, max 50 chars)
- secondary_cta_link (string, URL format)
- background_video_url (string, nullable, URL format)

**Display**: Full-screen centered layout with gradient background, large typography

---

### 2. Services Section
**Purpose**: Showcase what services the agency offers

**Content**:
- Section title and description
- Grid of service cards (4-6 services)
- Each service has: icon, name, short description
- Hover effects on cards

**Data Model**: `Service`
- name (string, required, max 100 chars)
- slug (string, unique, lowercase)
- icon (string, SVG code or icon class, max 5000 chars)
- short_description (text, required, max 150 chars)
- full_description (text, nullable)
- order (integer, default 0)
- is_visible (boolean, default true)

**Display**: 2 columns on mobile, 3 columns on desktop, card hover lift effect

---

### 3. Portfolio Section
**Purpose**: Showcase completed projects with filtering

**Content**:
- Section title
- Category filters (All, Web Design, Branding, Mobile Apps, etc.)
- Grid of project cards
- Each card: thumbnail image, title, category, client name
- Click to view project details (modal or separate page)

**Data Model**: `Project`
- title (string, required, max 150 chars)
- slug (string, unique, lowercase)
- client_name (string, max 100 chars)
- category_id (foreign key to categories table)
- thumbnail_image (string, path to storage)
- featured_image (string, path to storage)
- description (text, required, min 100 chars)
- technologies_used (json array)
- project_url (string, nullable, URL format)
- completion_date (date)
- is_featured (boolean, default false)
- is_visible (boolean, default true)
- order (integer, default 0)

**Data Model**: `Category`
- name (string, required, unique, max 50 chars)
- slug (string, unique, lowercase)
- is_visible (boolean, default true)

**Display**: Masonry grid or 3-column grid, real-time filtering without page reload

---

### 4. About Section
**Purpose**: Tell the agency's story and values

**Content**:
- About text (2-3 paragraphs)
- Company stats (years in business, projects completed, team size, awards)
- Mission/values list

**Admin Fields** (in site_settings):
- about_title (string, max 100 chars)
- about_text (text, rich text, min 200 chars)
- stats (json array):
  - number (string, e.g., "10+")
  - label (string, e.g., "Years Experience")

**Display**: Text on left, stats on right (desktop), stacked on mobile

---

### 5. Team Section
**Purpose**: Introduce team members

**Content**:
- Section title
- Grid of team member cards
- Each member: photo, name, role, bio, social links

**Data Model**: `TeamMember`
- name (string, required, max 100 chars)
- role (string, required, max 100 chars)
- bio (text, max 300 chars)
- photo (string, path to storage)
- linkedin_url (string, nullable, URL format)
- twitter_url (string, nullable, URL format)
- email (string, nullable, email format)
- order (integer, default 0)
- is_visible (boolean, default true)

**Display**: 2 columns mobile, 4 columns desktop, hover effect reveals bio

---

### 6. Testimonials Section
**Purpose**: Build credibility with client feedback

**Content**:
- Section title
- Slider/carousel of testimonials
- Each testimonial: quote, client name, company, logo (optional)

**Data Model**: `Testimonial`
- quote (text, required, min 50 chars, max 500 chars)
- client_name (string, required, max 100 chars)
- client_role (string, max 100 chars)
- company_name (string, max 100 chars)
- company_logo (string, path to storage, nullable)
- rating (integer, 1-5, nullable)
- order (integer, default 0)
- is_visible (boolean, default true)

**Display**: Carousel with 1 testimonial visible at a time, auto-rotate every 5 seconds

---

### 7. Blog/Insights Section
**Purpose**: Share industry insights and company news

**Content**:
- Section title
- Latest 3-6 blog posts
- Each post: thumbnail, title, excerpt, date, read time
- "View All Posts" link

**Data Model**: `BlogPost`
- title (string, required, unique, max 200 chars)
- slug (string, unique, lowercase)
- excerpt (text, required, max 200 chars)
- content (text, rich text, required, min 500 chars)
- thumbnail_image (string, path to storage)
- author_id (foreign key to users or team_members)
- published_at (datetime)
- read_time (integer, minutes, calculated or manual)
- category (string, max 50 chars)
- tags (json array)
- is_featured (boolean, default false)
- is_published (boolean, default false)

**Display**: 3 columns on desktop, 1 column mobile, show latest 6 published posts

---

### 8. Contact Section
**Purpose**: Make it easy for clients to reach out

**Content**:
- Section title and description
- Contact form with fields:
  - Name (required)
  - Email (required)
  - Phone (optional)
  - Service interested in (dropdown)
  - Message (required)
  - Budget range (optional dropdown)
- Contact information (email, phone, address)
- Social media links
- Office location map (Google Maps embed)

**Data Model**: `ContactSubmission`
- name (string, required, max 100 chars)
- email (string, required, email format, max 255 chars)
- phone (string, nullable, max 20 chars)
- service (string, nullable, max 100 chars)
- message (text, required, min 20 chars, max 1000 chars)
- budget_range (string, nullable, max 50 chars)
- status (enum: 'new', 'contacted', 'closed', default 'new')
- submitted_at (timestamp)

**Admin Fields** (in site_settings):
- contact_email (string, email format)
- contact_phone (string, max 20 chars)
- office_address (text, max 300 chars)
- google_maps_embed_url (text, URL format)
- office_hours (text, max 200 chars)

**Validation**:
- Name: required, string, max 100 chars
- Email: required, email, max 255 chars
- Phone: nullable, string, format (XXX) XXX-XXXX
- Service: nullable, string, in predefined list
- Message: required, string, min 20 chars, max 1000 chars
- Budget: nullable, string, in predefined ranges

**Display**: Form on left, contact info on right (desktop), stacked on mobile

---

### 9. Newsletter Section
**Purpose**: Collect email subscribers

**Content**:
- Heading
- Brief description
- Email input field
- Subscribe button

**Data Model**: `NewsletterSubscriber`
- email (string, required, unique, email format, max 255 chars)
- subscribed_at (timestamp)
- is_active (boolean, default true)

**Display**: Full-width banner with centered content, gradient background

---

### 10. Footer
**Purpose**: Navigation and legal information

**Content**:
- Company logo
- Quick links (Services, Portfolio, About, Blog, Contact)
- Social media icons
- Copyright text
- Privacy Policy & Terms of Service links

**Admin Fields** (in site_settings):
- footer_text (string, max 200 chars)
- privacy_policy_url (string, nullable, URL format)
- terms_url (string, nullable, URL format)

**Display**: 4-column layout on desktop, stacked on mobile

---

## Content Models Summary

1. **Service** - Services offered by agency
   - name, slug, icon, short_description, full_description
   - order, is_visible

2. **Project** - Portfolio projects
   - title, slug, client_name, category_id, thumbnail_image, featured_image
   - description, technologies_used, project_url, completion_date
   - is_featured, is_visible, order
   - belongsTo: Category

3. **Category** - Project categories
   - name, slug, is_visible
   - hasMany: Projects

4. **TeamMember** - Team members
   - name, role, bio, photo, linkedin_url, twitter_url, email
   - order, is_visible

5. **Testimonial** - Client testimonials
   - quote, client_name, client_role, company_name, company_logo, rating
   - order, is_visible

6. **BlogPost** - Blog articles
   - title, slug, excerpt, content, thumbnail_image, author_id
   - published_at, read_time, category, tags
   - is_featured, is_published

7. **ContactSubmission** - Contact form submissions
   - name, email, phone, service, message, budget_range
   - status, submitted_at

8. **NewsletterSubscriber** - Email subscribers
   - email, subscribed_at, is_active

9. **SiteSettings** - Site-wide settings (single row, JSON columns for sections)

---

## Specific Requirements

### Contact Form
- Send email notification to admin@example.com on submission
- Store submissions in database with status
- Show success message after submission
- Client-side validation + server-side validation
- Spam protection (honeypot field or reCAPTCHA optional)

### Blog
- Rich text editor in admin (TinyMCE or CKEditor)
- Featured posts option
- Category filtering
- Reading time auto-calculated from content length
- SEO fields (meta title, description) per post

### Portfolio Filtering
- Real-time filtering without page reload using Vue
- Smooth fade animation when filtering
- Show all projects by default
- Active filter button highlighted

### Animations
- Scroll-triggered fade-in animations using Intersection Observer
- Hover lift effect on all cards
- Smooth page transitions
- Loading animation on initial load

### Performance
- Lazy load images below the fold
- Cache all API responses in Redis
- Optimize images on upload (max 2000px width)
- Minify CSS/JS with Vite

### SEO
- Dynamic meta tags per page
- Open Graph images for blog posts (use thumbnail)
- Structured data for Organization
- Alt text required for all uploaded images
- Clean URLs with slugs

---

## Design Preferences

### Color Scheme

**Dark Theme** (default):
- Primary: Deep Navy Blue (#0f172a)
- Accent: Vibrant Orange (#f97316)
- Background: Very Dark (#030712)
- Text: Off-white (#f8fafc)
- Border: Subtle gray (rgba(255,255,255,0.1))

**Light Theme**:
- Primary: Navy Blue (#1e40af)
- Accent: Bright Orange (#ea580c)
- Background: Off-white (#fafafa)
- Text: Dark Gray (#0f172a)
- Border: Subtle dark (rgba(0,0,0,0.1))

### Typography
- Headings: "Poppins" (bold, modern) - Google Fonts
- Body: "Inter" (clean, readable) - Google Fonts
- Code/labels: "JetBrains Mono" - Google Fonts

### Style Inspiration
- Apple.com - Clean, spacious, minimalist
- Stripe.com - Modern, professional, subtle animations
- Awwwards.com - Creative, bold, interactive

### Visual Elements
- Subtle grid patterns in background
- Gradient accents on CTA buttons
- Card-based layouts with soft shadows
- Rounded corners (12px radius on cards, 8px on buttons)
- Generous whitespace (24px between sections minimum)
- Glass morphism on nav bar (backdrop-blur)
- Hover lift effect (translateY(-4px) with shadow increase)

---

## Third-Party Integrations

### Email (Required)
- Use Laravel Mail for contact form notifications
- Send to admin email specified in settings
- Include all form data in email

### Analytics (Optional)
- Google Analytics 4 tracking code
- Add to blade template

### Maps (Required)
- Google Maps embed for office location
- Admin can paste embed URL

---

## Admin Panel Requirements

### Dashboard
- Quick stats cards:
  - Total projects (with featured count)
  - Total team members
  - Total testimonials
  - Total blog posts (published/draft)
- Recent contact submissions (last 5, unread count badge)
- Recent newsletter subscribers (last 5)
- Quick actions: Add New Project, Add Blog Post, View Submissions

### Content Sections

1. **Services Management**
   - List view with drag-to-reorder
   - Add/Edit/Delete
   - Toggle visibility
   - Icon input (paste SVG code or icon class)

2. **Portfolio Management**
   - List view with filters (by category, featured, visible)
   - Add/Edit/Delete
   - Image upload (thumbnail and featured)
   - Category assignment
   - Toggle featured and visibility
   - Technologies input (tags)

3. **Categories Management**
   - Simple list
   - Add/Edit/Delete
   - Toggle visibility

4. **Team Management**
   - Grid view with photos
   - Drag-to-reorder
   - Add/Edit/Delete
   - Photo upload
   - Social links input

5. **Testimonials Management**
   - List view
   - Drag-to-reorder
   - Add/Edit/Delete
   - Company logo upload (optional)
   - Rating selection (1-5 stars)

6. **Blog Management**
   - List view with filters (published/draft, featured)
   - Add/Edit/Delete
   - Rich text editor for content
   - Thumbnail upload
   - SEO fields (meta title, description)
   - Publish/draft toggle
   - Featured toggle

7. **Contact Submissions**
   - Table view with filters (by status)
   - View submission details
   - Mark as contacted/closed
   - Delete
   - Export to CSV

8. **Newsletter Subscribers**
   - List view
   - Export to CSV
   - Unsubscribe button

9. **Site Settings**
   - Tabs for different sections:
     - Hero (headline, subheading, CTAs, background video)
     - About (text, stats)
     - Contact (email, phone, address, map, hours)
     - Footer (text, privacy/terms URLs)

### Media Management
- All image uploads show preview before save
- Delete old image when uploading new one
- Images stored in `storage/app/public/`
- Auto-generate thumbnails where needed

### Cache Management
- "Rebuild Cache" button in main nav or dashboard
- Shows last cache rebuild time
- Clicking rebuilds all cached API responses

---

## Validation Rules

### Project
- title: required, unique, max 150 chars
- client_name: required, max 100 chars
- category_id: required, exists in categories table
- thumbnail_image: required on create, image (jpg,png,webp), max 2MB
- description: required, min 100 chars

### Blog Post
- title: required, unique, max 200 chars
- slug: required, unique, lowercase, regex: /^[a-z0-9-]+$/
- excerpt: required, max 200 chars
- content: required, min 500 chars
- thumbnail_image: required on create, image, max 2MB
- published_at: required if is_published is true, date

### Contact Form
- name: required, string, max 100 chars
- email: required, email, max 255 chars
- phone: nullable, string, max 20 chars
- service: nullable, string, in:['Web Design', 'Branding', 'Mobile Apps', 'Other']
- message: required, string, min 20 chars, max 1000 chars
- budget: nullable, string, in:['< $5K', '$5K-$10K', '$10K-$25K', '$25K+']

---

## Expected User Flow

### Visitor Journey
1. Land on Hero → See compelling headline and agency purpose
2. Scroll to Services → Understand what agency offers
3. View Portfolio → See quality of work, filter by category to find relevant projects
4. Read Testimonials → Build trust through client feedback
5. Check About/Team → Learn about company culture and people
6. Read Blog (optional) → Get industry insights and expertise proof
7. Submit Contact Form → Start project discussion or inquiry
8. (Optional) Subscribe to newsletter

### Admin Journey
1. Login to admin panel at /admin/login
2. View dashboard with quick stats and recent activity
3. Navigate to Projects section
4. Add new project with images, description, category
5. Save and verify it appears on public site
6. Go to Services, reorder by dragging
7. Edit About section text in Site Settings
8. Check Contact Submissions, mark one as contacted
9. Click "Rebuild Cache" to update public site
10. Logout

---

## Success Criteria

The website is successful if:

- ✅ All sections render correctly on mobile (320px) and desktop (1920px)
- ✅ Content can be fully managed from admin panel without code changes
- ✅ Theme switching works smoothly with zoom-blur transition
- ✅ SEO meta tags are present and correct on all pages
- ✅ Contact form validates, submits, stores, and sends email
- ✅ Portfolio filtering works without page reload
- ✅ Images are optimized and lazy-loaded
- ✅ Page loads under 2 seconds on 3G connection
- ✅ Cache invalidation works correctly when content updated
- ✅ All validations prevent invalid data from being saved
- ✅ Drag-and-drop reordering works for services, team, testimonials
- ✅ No console errors or warnings in browser
- ✅ All links work and open correctly (internal/external)
- ✅ Accessible with keyboard navigation (tab through all interactive elements)
- ✅ WCAG 2.1 AA compliant (color contrast, alt text, ARIA labels)

---

**Site Name**: CreativeFlow Agency
**Target Audience**: Startups and businesses looking for digital services
**Tone**: Professional, modern, creative, trustworthy, approachable
**Launch Timeline**: ASAP

---

<a name="example-2-github-package-documentation"></a>
# 7. EXAMPLE 2: GITHUB PACKAGE DOCUMENTATION

[Note: Use this example as reference for documentation sites, GitHub repos, NPM/Composer packages]

## Site Type
Modern documentation and introduction page for a GitHub repository/package with markdown support and media management.

## Site Description
A clean, developer-friendly documentation site for showcasing an open-source project/package. Features markdown-powered documentation, image gallery for screenshots, useful resource links, and easy installation instructions. Perfect for Laravel packages, NPM modules, Python libraries, or any open-source project.

---

## Sections Required

### 1. Hero Section
**Purpose**: Immediate project identification and quick action

**Content**:
- Project name/logo
- Tagline/short description
- Key badges (version, license, downloads, stars)
- Primary CTA (GitHub link)
- Secondary CTA (Documentation link)
- Installation command with copy button

**Admin Fields** (in site_settings):
- project_name (string, required, max 100 chars)
- tagline (string, required, max 150 chars)
- logo_path (string, nullable, path to storage)
- github_url (string, required, URL format, must start with https://github.com/)
- documentation_url (string, nullable, URL format)
- npm_package_name (string, nullable, max 100 chars)
- version (string, required, max 20 chars, e.g., "v2.3.1")
- license (string, required, max 50 chars, e.g., "MIT")
- installation_command (text, required, max 500 chars, e.g., "composer require vendor/package")

**Display**: Centered layout with gradient background, large typography, code block with copy button

---

### 2. Quick Start Section
**Purpose**: Get developers up and running immediately

**Content**:
- Installation instructions
- Basic usage example with code highlighting
- Requirements (PHP version, dependencies, etc.)
- Quick configuration steps

**Admin Fields** (in site_settings):
- requirements (json array):
  ```json
  [
    {"name": "PHP 8.1+", "description": "Minimum PHP version required"},
    {"name": "Laravel 11", "description": "Framework requirement"}
  ]
  ```
- installation_steps (json array):
  ```json
  [
    {
      "step_number": 1,
      "instruction": "Install via Composer",
      "code_snippet": "composer require vendor/package"
    }
  ]
  ```

**Display**: Step-by-step format with numbered list, code blocks with syntax highlighting and copy buttons

---

### 3. Features Section
**Purpose**: Highlight what the package can do

**Content**:
- Grid of feature cards
- Each feature: icon, title, description
- Optional code example per feature

**Data Model**: `Feature`
- title (string, required, max 100 chars)
- description (text, required, max 300 chars)
- icon (string, SVG code or icon class, max 5000 chars)
- code_example (text, nullable, max 2000 chars)
- order (integer, default 0)
- is_visible (boolean, default true)

**Display**: 3-column grid on desktop, 1 column on mobile, hover effects

---

### 4. Documentation Section
**Purpose**: Complete documentation rendered from markdown

**Content**:
- Multiple documentation sections/chapters
- Markdown content rendered with:
  - Headings (h1-h6)
  - Code blocks with syntax highlighting
  - Tables
  - Lists (ordered/unordered)
  - Links and images
  - Blockquotes
- Table of contents (auto-generated from headings)
- Smooth scroll to sections

**Data Model**: `DocumentationSection`
- title (string, required, max 150 chars)
- slug (string, unique, for anchor links, lowercase)
- markdown_content (text, required, min 100 chars)
- order (integer, default 0)
- is_visible (boolean, default true)
- parent_id (integer, nullable, foreign key to self for nested sections)

**Admin Interface**:
- Rich markdown editor (CodeMirror or SimpleMDE)
- Live preview toggle (split-screen view)
- Markdown syntax helper buttons (bold, italic, heading, code, link, image)
- Insert image button (opens media picker showing uploaded images)
- Auto-save draft every 30 seconds
- Full-screen editing mode

**Frontend Rendering**:
- Use markdown parser: marked.js or markdown-it
- Syntax highlighting: Prism.js or Highlight.js
- Supported languages: PHP, JavaScript, Python, Bash, JSON, YAML, HTML, CSS, SQL
- Responsive code blocks with horizontal scroll
- Auto-generate anchor links for all headings
- Copy button on every code block

**Display**: Two-column layout (sticky TOC sidebar + content) on desktop, stacked on mobile with floating TOC button

---

### 5. Screenshots/Media Gallery Section
**Purpose**: Visual demonstration of the package/project

**Content**:
- Grid of screenshots/images
- Click to view full-size (lightbox)
- Captions for each image
- Filter by type (screenshot, diagram, demo)

**Data Model**: `MediaFile`
- filename (string, required, unique, max 255 chars)
- original_filename (string, required, max 255 chars)
- path (string, required, relative to storage, max 500 chars)
- public_url (string, generated, URL format)
- type (enum: 'screenshot', 'diagram', 'demo', 'other', default 'screenshot')
- caption (string, nullable, max 200 chars)
- file_size (integer, bytes)
- mime_type (string, max 100 chars)
- width (integer, nullable, pixels)
- height (integer, nullable, pixels)
- uploaded_at (timestamp)

**Admin Interface - File Upload**:
- Drag-and-drop dropzone at top
- Browse button for traditional file selection
- Multiple file upload support
- Progress bar during upload
- Preview thumbnails after upload
- Upload constraints:
  - File type: image only (jpg, jpeg, png, gif, webp, svg)
  - Max size: 5MB per file
  - Max dimensions: 4000x4000 (auto-resize if larger)

**Admin Interface - Image Gallery Table**:

Table with columns:
1. **Preview**: Thumbnail (80x80px, clickable to view full size)
2. **Filename**: Original filename (truncated if long)
3. **Public URL**: Full URL with copy-to-clipboard button
4. **Type**: Dropdown (screenshot, diagram, demo, other) - inline editable
5. **Caption**: Text input - inline editable
6. **Size**: Formatted file size (e.g., "1.2 MB")
7. **Dimensions**: Width x Height (e.g., "1920x1080")
8. **Uploaded**: Relative date (e.g., "2 days ago")
9. **Actions**: View icon | Copy URL icon | Delete icon

Table features:
- Sortable columns (click header to sort)
- Search/filter by filename or caption
- Pagination (20 items per page)
- Bulk select checkboxes
- Bulk delete action
- Click "Copy URL" shows toast notification "Copied!"
- Delete button shows confirmation modal with image preview

**Storage**:
- Path: `storage/app/public/media/`
- Subdirectories:
  - `originals/` - Full-size images
  - `thumbnails/` - 300x300 thumbnails
- Public URL structure: `/storage/media/originals/{filename}`
- Symbolic link: `php artisan storage:link` (document in README)

**Processing on Upload**:
1. Validate file (type, size)
2. Sanitize filename (remove spaces, special chars)
3. Generate unique filename: `timestamp_randomstring.extension`
4. Store original in `storage/app/public/media/originals/`
5. Generate thumbnail (300x300, crop to fit) in `thumbnails/`
6. Optimize image (compress to 85% quality)
7. Extract dimensions (width, height)
8. Save metadata to database

**Validation**:
- file: required, image (jpg,jpeg,png,gif,webp,svg), max 5120 (5MB)
- caption: nullable, string, max 200 chars
- type: required, in:['screenshot','diagram','demo','other']

**Display**: Masonry grid with hover overlay showing caption, lightbox modal on click with navigation

---

### 6. Useful Links Section
**Purpose**: External resources and related links

**Content**:
- List of helpful links
- Each link: icon, title, description, URL
- Categories (Documentation, Community, Related Projects, Tools)

**Data Model**: `UsefulLink`
- title (string, required, max 100 chars)
- url (string, required, URL format, max 500 chars)
- description (text, max 200 chars)
- icon (string, SVG code or icon class, max 5000 chars)
- category (enum: 'documentation', 'community', 'related', 'tool')
- order (integer, default 0)
- is_visible (boolean, default true)
- opens_new_tab (boolean, default true)

**Display**: Card-based layout grouped by category, 2 columns on desktop

---

### 7. API Reference Section (Optional)
**Purpose**: Document API endpoints or class methods

**Content**:
- List of classes/methods or endpoints
- Each entry: name, parameters, return type, description, example

**Data Model**: `ApiReference`
- name (string, required, max 150 chars)
- type (enum: 'class', 'method', 'function', 'endpoint')
- signature (text, e.g., "public function doSomething(string $param): bool")
- description (text, required)
- parameters (json array):
  ```json
  [
    {
      "name": "param1",
      "type": "string",
      "description": "Description of parameter",
      "required": true
    }
  ]
  ```
- return_type (string, max 50 chars)
- return_description (text)
- code_example (text)
- order (integer, default 0)
- is_visible (boolean, default true)

**Display**: Expandable accordion with syntax-highlighted examples

---

### 8. Changelog Section
**Purpose**: Version history and updates

**Content**:
- Timeline of versions
- Each version: number, date, changes by type

**Data Model**: `ChangelogEntry`
- version (string, required, max 20 chars, e.g., "2.1.0")
- release_date (date, required)
- changes (json array):
  ```json
  [
    {
      "type": "added",
      "description": "New feature X"
    },
    {
      "type": "fixed",
      "description": "Bug in Y"
    }
  ]
  ```
  Types: added, changed, deprecated, removed, fixed, security
- is_published (boolean, default false)

**Display**: Vertical timeline with color-coded change types (green=added, blue=changed, yellow=deprecated, red=removed, purple=fixed, orange=security)

---

### 9. Contributing/Footer Section
**Purpose**: Encourage contributions and provide project info

**Content**:
- How to contribute link
- License information
- GitHub repository link
- NPM/Packagist link
- Social links (Twitter, Discord, Slack)
- Star count (fetched from GitHub API, optional)

**Admin Fields** (in site_settings):
- contributing_url (string, nullable, URL format)
- twitter_handle (string, nullable, max 50 chars)
- discord_url (string, nullable, URL format)
- packagist_url (string, nullable, URL format)
- npm_url (string, nullable, URL format)

**Display**: Clean footer with icon links and stats badges

---

## Content Models Summary

1. **ProjectInfo** (site_settings JSON column) - Main project information
2. **Feature** - Package features with icons and examples
3. **DocumentationSection** - Markdown documentation chapters (self-referential for nesting)
4. **MediaFile** - Uploaded images/screenshots with metadata
5. **UsefulLink** - External resource links categorized
6. **ApiReference** - API/method documentation (optional)
7. **ChangelogEntry** - Version history with typed changes
8. **SiteSettings** - Site-wide settings (single row, JSON columns)

---

## Specific Requirements

### Markdown Rendering

**Backend**:
- Store raw markdown text in `markdown_content` field
- No processing on backend (keep it raw)

**Frontend**:
- Parse markdown to HTML using marked.js or markdown-it
- Configuration:
  ```javascript
  marked.setOptions({
    highlight: function(code, lang) {
      return Prism.highlight(code, Prism.languages[lang], lang);
    },
    gfm: true, // GitHub Flavored Markdown
    breaks: true, // Convert \n to <br>
    tables: true,
    xhtml: false
  });
  ```
- Syntax highlighting with Prism.js:
  - Languages: PHP, JavaScript, Python, Bash, JSON, YAML, HTML, CSS, SQL, TypeScript
  - Theme: Use `prism-tomorrow.css` for dark mode, `prism.css` for light mode
  - Plugins: line-numbers, copy-to-clipboard, toolbar
- Security: Sanitize HTML output with DOMPurify to prevent XSS
- Features:
  - Auto-linking of URLs
  - Task lists support (`- [ ]` and `- [x]`)
  - Tables with proper styling
  - Blockquotes with accent border
  - Inline code with background highlight

**Table of Contents**:
- Auto-generate from h2 and h3 headings
- Extract heading text and create slug
- Create navigation links with smooth scroll

### File Upload System

**Upload Interface** (Vue component):
```vue
<template>
  <div class="upload-zone">
    <input type="file" multiple accept="image/*" @change="handleFiles" ref="fileInput">
    <div @drop.prevent="handleDrop" @dragover.prevent>
      <p>Drag images here or click to browse</p>
    </div>
    <div v-for="file in uploadQueue" :key="file.name">
      <img :src="file.preview" alt="Preview">
      <progress :value="file.progress" max="100"></progress>
    </div>
  </div>
</template>
```

**Upload Endpoint** (Laravel):
```php
public function upload(Request $request)
{
    $request->validate([
        'files.*' => 'required|image|mimes:jpg,jpeg,png,gif,webp,svg|max:5120',
        'type' => 'required|in:screenshot,diagram,demo,other'
    ]);

    $uploadedFiles = [];

    foreach ($request->file('files') as $file) {
        // Generate unique filename
        $filename = time() . '_' . Str::random(10) . '.' . $file->extension();

        // Store original
        $file->storeAs('public/media/originals', $filename);

        // Generate thumbnail using Intervention Image
        $image = Image::make($file);

        // Resize if too large
        if ($image->width() > 4000 || $image->height() > 4000) {
            $image->resize(4000, 4000, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        // Optimize
        $image->encode('jpg', 85);

        // Save original
        $image->save(storage_path('app/public/media/originals/' . $filename));

        // Create thumbnail
        $thumbnail = Image::make($file)->fit(300, 300);
        $thumbnail->save(storage_path('app/public/media/thumbnails/' . $filename));

        // Save to database
        $media = MediaFile::create([
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'path' => 'public/media/originals/' . $filename,
            'public_url' => Storage::url('media/originals/' . $filename),
            'type' => $request->type,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'width' => $image->width(),
            'height' => $image->height()
        ]);

        $uploadedFiles[] = $media;
    }

    return response()->json([
        'success' => true,
        'files' => $uploadedFiles
    ]);
}
```

**Delete Endpoint**:
```php
public function delete(MediaFile $media)
{
    // Delete physical files
    Storage::delete($media->path);
    Storage::delete('public/media/thumbnails/' . $media->filename);

    // Delete database record
    $media->delete();

    return response()->json(['success' => true]);
}
```

### Code Syntax Highlighting

**Setup** (in Vue component):
```javascript
import Prism from 'prismjs';
import 'prismjs/themes/prism-tomorrow.css';
import 'prismjs/components/prism-php';
import 'prismjs/components/prism-javascript';
import 'prismjs/components/prism-python';
import 'prismjs/components/prism-bash';
import 'prismjs/components/prism-json';
import 'prismjs/components/prism-yaml';
import 'prismjs/plugins/line-numbers/prism-line-numbers';
import 'prismjs/plugins/toolbar/prism-toolbar';
import 'prismjs/plugins/copy-to-clipboard/prism-copy-to-clipboard';

onMounted(() => {
  Prism.highlightAll();
});
```

**Custom Styles**:
```css
pre[class*="language-"] {
  @apply rounded-lg my-4;
}

.copy-to-clipboard-button {
  @apply px-3 py-1 text-sm bg-accent text-white rounded;
}
```

### Copy-to-Clipboard Functionality

```javascript
function copyToClipboard(text) {
  navigator.clipboard.writeText(text).then(() => {
    // Show toast notification
    showToast('Copied to clipboard!', 'success');
  }).catch(err => {
    // Fallback for older browsers
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    showToast('Copied!', 'success');
  });
}
```

---

## Design Preferences

### Color Scheme

**Dark Theme** (default):
- Primary: Deep Blue (#0f172a)
- Accent: Cyan (#06b6d4)
- Background: Very Dark (#020617)
- Text: Off-white (#f8fafc)
- Code Block Background: Monokai-inspired (#272822)
- Border: Subtle gray (rgba(255,255,255,0.08))

**Light Theme**:
- Primary: Navy Blue (#1e3a8a)
- Accent: Blue (#3b82f6)
- Background: Off-white (#fafafa)
- Text: Dark Gray (#1e293b)
- Code Block Background: Light Gray (#f1f5f9)
- Border: Subtle dark (rgba(0,0,0,0.1))

### Typography
- Headings: "Inter" (semi-bold, clean) - Google Fonts
- Body: "Inter" (regular, readable) - Google Fonts
- Code: "JetBrains Mono" (monospace) - Google Fonts

### Style Inspiration
- GitHub.com documentation pages - Clean, organized
- docs.laravel.com - Readable, well-structured
- tailwindcss.com/docs - Modern, colorful, searchable
- stripe.com/docs - Professional, clear hierarchy

### Visual Elements
- Code blocks with rounded corners (12px radius)
- Subtle shadows on documentation cards
- Smooth transitions on all interactions (300ms ease)
- Badge components for version/license/stats
- Iconography from Heroicons or Lucide
- Gradient backgrounds in hero section (subtle, not overwhelming)
- Glass morphism on navigation (backdrop-blur-md)
- Sticky table of contents sidebar
- Floating action button for mobile TOC

---

## Third-Party Integrations

### GitHub API (Optional)
- Fetch repository stats (stars, forks, open issues)
- Display as badges in hero section
- Update daily via Laravel scheduled task
- Cache results in Redis for 24 hours
- Graceful fallback if API fails

**Implementation**:
```php
// In a scheduled task
$response = Http::get("https://api.github.com/repos/{owner}/{repo}");
$data = $response->json();

Cache::put('github_stats', [
    'stars' => $data['stargazers_count'],
    'forks' => $data['forks_count'],
    'issues' => $data['open_issues_count']
], now()->addDay());
```

### Syntax Highlighting Libraries
- **Primary**: Prism.js (lightweight, customizable)
- **CDN**: Load from CDN or bundle with Vite
- **Themes**: Include both prism-tomorrow.css (dark) and prism.css (light)
- **Custom theme switching**: Toggle stylesheet based on app theme

### Markdown Parser
- **Primary**: marked.js (fast, reliable)
- **Alternative**: markdown-it (more plugins)
- **Plugins**: GFM support, task lists, table support

---

## Admin Panel Requirements

### Dashboard
- Quick stats cards:
  - Total features
  - Documentation sections
  - Media files (with storage usage: "X MB / 500 MB")
  - Useful links
- Recent uploads (last 5 images with thumbnails)
- Storage usage chart
- Quick actions:
  - Upload Images
  - Add Documentation Section
  - Edit Project Info
  - Rebuild Cache

### Content Sections

#### 1. Project Settings
- Form with tabs:
  - **General**: name, tagline, logo upload, version, license
  - **Links**: GitHub URL, documentation URL, NPM/Packagist
  - **Installation**: installation command editor
  - **Requirements**: List with add/remove (name, description)

#### 2. Features Management
- List view with drag-and-drop reorder
- Add/Edit modal:
  - Title input
  - Description textarea
  - Icon input (paste SVG or icon class)
  - Code example editor with syntax highlighting preview
- Delete with confirmation
- Bulk visibility toggle

#### 3. Documentation Editor
- Sidebar: List of sections with hierarchical tree view (parent/child)
- Main area: Split-screen editor
  - Left: Markdown editor with toolbar
    - Buttons: Bold, Italic, Heading (H1-H6), Code, Link, Image, List, Quote
    - Insert image button opens media picker
    - Keyboard shortcuts (Cmd/Ctrl+B for bold, etc.)
  - Right: Live preview with rendered markdown
- Top bar:
  - Section title input
  - Parent section dropdown (for nesting)
  - Visibility toggle
  - Save button (with auto-save indicator)
  - Full-screen toggle
- Features:
  - Auto-save draft every 30 seconds
  - Manual save button
  - "Publish" button to make visible
  - Preview in new tab button

#### 4. Media Manager
**Upload Zone** (top of page):
- Large drag-and-drop area
- "Browse Files" button
- Multiple file selection
- Live upload progress bars
- Preview thumbnails appear immediately

**Gallery Table** (below upload):
- Columns as specified in MediaFile model section
- Features:
  - Sort by: filename, size, date (click column header)
  - Search box (filters filename and caption)
  - Type filter dropdown (all, screenshot, diagram, demo, other)
  - Items per page: 20 (with pagination)
  - Bulk select checkboxes (select all, select individual)
  - Bulk actions: Delete selected
- Row actions:
  - 👁️ View full size (opens lightbox)
  - 📋 Copy URL (copies public_url, shows toast)
  - 🗑️ Delete (confirmation modal with preview)
- Inline editing:
  - Click caption to edit
  - Click type dropdown to change
  - Auto-save on blur

**Delete Confirmation Modal**:
- Show image preview (thumbnail)
- Show filename and size
- Warning text: "This action cannot be undone"
- Buttons: Cancel (gray) | Delete (red)
- On confirm: Delete file from storage and database

#### 5. Useful Links Management
- List view grouped by category
- Drag-to-reorder within each category
- Add/Edit modal:
  - Title, URL, Description
  - Category dropdown
  - Icon input (SVG or class)
  - "Opens in new tab" checkbox
- Delete with confirmation
- Visibility toggle

#### 6. API Reference (Optional)
- List view grouped by type (class, method, function, endpoint)
- Add/Edit form:
  - Name, Type, Signature
  - Description (textarea)
  - Parameters (repeatable fields):
    - Name, Type, Description, Required checkbox
    - Add/Remove parameter buttons
  - Return type, Return description
  - Code example (syntax highlighted editor)
- Reorder
- Visibility toggle

#### 7. Changelog
- List of versions (newest first)
- Add new version form:
  - Version number input (e.g., "2.1.0")
  - Release date picker
  - Changes section:
    - Type dropdown (added, changed, deprecated, removed, fixed, security)
    - Description textarea
    - Add/Remove change buttons
  - Publish checkbox
- Edit existing version
- Delete version

#### 8. Cache Management
- Located in main navigation or dashboard
- Shows:
  - Last cache rebuild timestamp
  - Cache size (if available)
- "Rebuild Cache" button:
  - Click to clear and rebuild all Redis cache
  - Shows loading spinner
  - Success message after completion
  - Updates last rebuild timestamp

---

## Validation Rules

### Project Settings
- project_name: required, string, max 100 chars
- tagline: required, string, max 150 chars
- github_url: required, URL, starts with "https://github.com/", max 500 chars
- version: required, string, max 20 chars, regex: `/^v?\d+\.\d+\.\d+$/` (semantic versioning)
- license: required, string, max 50 chars
- installation_command: required, string, max 500 chars

### Feature
- title: required, string, max 100 chars
- description: required, string, max 300 chars
- icon: nullable, string, max 5000 chars
- code_example: nullable, string, max 2000 chars

### Documentation Section
- title: required, string, max 150 chars
- slug: required, unique, lowercase, regex: `/^[a-z0-9-]+$/`, max 150 chars
- markdown_content: required, string, min 100 chars
- parent_id: nullable, exists:documentation_sections,id

### Media File (on upload)
- files.*: required, file, image, mimes:jpg,jpeg,png,gif,webp,svg, max:5120 (5MB)
- type: required, in:screenshot,diagram,demo,other
- caption: nullable, string, max 200 chars

### Useful Link
- title: required, string, max 100 chars
- url: required, URL, max 500 chars
- description: required, string, max 200 chars
- category: required, in:documentation,community,related,tool

---

## Expected User Flow

### Visitor Journey
1. Land on Hero → See project name, read tagline, understand purpose at a glance
2. View installation command → Copy with one click
3. Scroll to Quick Start → Follow step-by-step setup instructions
4. Check Features → Understand what the package can do
5. Read Documentation → Learn how to use it with code examples
6. View Screenshots → See it in action with visual examples
7. Check Useful Links → Find related resources (GitHub, Discord, docs)
8. Click GitHub link → Star or fork the repository
9. (Optional) Check Changelog → See recent updates and fixes
10. (Optional) View API Reference → Deep dive into methods

### Developer Journey (Admin)
1. Login to admin panel at /admin/login
2. View dashboard with project stats
3. Navigate to Project Settings
4. Update version number (e.g., from "v2.3.0" to "v2.3.1")
5. Go to Documentation Editor
6. Add new section: "Advanced Configuration"
7. Write markdown content with code examples
8. Click "Insert Image" button
9. Redirected to Media Manager
10. Upload 2 new screenshots via drag-and-drop
11. Wait for upload progress
12. See images in gallery table
13. Click "Copy URL" on first image
14. Return to Documentation Editor
15. Paste image URL in markdown: `![Screenshot](url)`
16. Preview rendered markdown
17. Save documentation section
18. Go to Changelog
19. Add new version "2.3.1" with changes:
    - Type: "added", Description: "New advanced configuration options"
    - Type: "fixed", Description: "Bug in cache invalidation"
20. Publish changelog entry
21. Click "Rebuild Cache" in nav
22. View public site to verify all changes
23. Logout

---

## Success Criteria

The website is successful if:

- ✅ Project information displays correctly in hero section
- ✅ Installation command has working copy button with toast feedback
- ✅ Markdown renders properly with headings, lists, tables, code blocks
- ✅ Code blocks have syntax highlighting for all specified languages
- ✅ Code blocks have functional copy buttons
- ✅ Images upload successfully via drag-and-drop
- ✅ Images appear in gallery table with all metadata
- ✅ Image public URLs are accessible and copyable
- ✅ Image delete removes file from storage and database
- ✅ Documentation sections render in correct order
- ✅ Table of contents auto-generates from markdown headings
- ✅ TOC links scroll smoothly to sections
- ✅ Nested documentation sections display hierarchically
- ✅ Useful links open correctly (new tab for external)
- ✅ Theme switching works on all sections including code blocks
- ✅ Mobile responsive (especially code blocks scroll horizontally)
- ✅ SEO meta tags include project name and tagline
- ✅ GitHub link works and opens in new tab
- ✅ Cache invalidation works from admin
- ✅ No console errors or warnings
- ✅ All images have proper alt text
- ✅ Markdown editor saves drafts automatically
- ✅ Admin can reorder features, links, and documentation sections
- ✅ File uploads respect size and type limits
- ✅ Storage path uses Laravel storage correctly

---

## Technical Notes

### Markdown Security
- Always sanitize HTML output to prevent XSS attacks
- Use DOMPurify.js on frontend:
  ```javascript
  import DOMPurify from 'dompurify';
  const cleanHTML = DOMPurify.sanitize(markedHTML);
  ```
- Whitelist allowed HTML tags (headings, lists, links, images, code, tables)
- Strip `<script>` tags and event handlers
- Escape user input before saving to database

### Image Optimization
- Use Intervention Image package for Laravel:
  ```bash
  composer require intervention/image
  ```
- Compress images to 85% quality (good balance)
- Generate WebP versions for modern browsers (optional enhancement)
- Lazy load images below the fold:
  ```html
  <img loading="lazy" src="..." alt="...">
  ```
- Use thumbnail URLs in gallery table for faster loading

### Performance
- Cache rendered markdown in Redis with key: `markdown_{section_id}`
- Invalidate markdown cache when section is updated
- Lazy load images in documentation and gallery
- Minify CSS/JS with Vite
- Use CDN for Prism.js and markdown libraries (optional)
- Paginate media gallery (20 per page)
- Index frequently queried columns (slug, is_visible, order)

### SEO
- Dynamic title: `{{ $project_name }} - {{ $tagline }}`
- Description from project tagline
- Open Graph image: Use first uploaded screenshot if available, fallback to logo
- Structured data for SoftwareApplication:
  ```json
  {
    "@context": "https://schema.org",
    "@type": "SoftwareApplication",
    "name": "Package Name",
    "description": "Package description",
    "applicationCategory": "DeveloperApplication",
    "operatingSystem": "PHP 8.1+",
    "softwareVersion": "2.3.1",
    "offers": {
      "@type": "Offer",
      "price": "0",
      "priceCurrency": "USD"
    }
  }
  ```
- Clean URLs for documentation sections: `/docs/{slug}`
- Sitemap including all documentation sections
- robots.txt allowing all except /admin

---

**Site Name**: Package Documentation Site
**Target Audience**: Developers looking to use the package/project
**Tone**: Professional, technical, clear, helpful, approachable
**Launch Timeline**: ASAP
**Example Package**: LaraCache Pro (Laravel caching package)

---

<a name="quick-reference"></a>
# 8. QUICK REFERENCE

## For Users: How to Use This Document

### Quick Start (5 Minutes)

1. **Copy this prompt to AI**:
   ```
   Generate a site structure for my website.

   [Paste Section 4: SITE STRUCTURE GENERATOR PROMPT]

   BLANK TEMPLATE:
   [Paste Section 5: BLANK TEMPLATE]

   EXAMPLE:
   [Paste Section 6 OR 7 depending on your site type]

   MY WEBSITE IDEA:
   I want to build a [type] website with [features].
   Target audience: [who]
   Key sections: [list]
   Design style: [description]
   ```

2. **Receive generated structure**

3. **Generate website code**:
   ```
   Build a Laravel website.

   [Paste Section 3: MAIN WEBSITE BUILDER PROMPT]

   SITE STRUCTURE:
   [Paste generated structure from step 2]
   ```

4. **Done!** Extract and deploy the code

---

## Website Types Supported

| Type | Use Example From | Features |
|------|-----------------|----------|
| Portfolio | Section 6 | Projects, skills, blog |
| Business/Agency | Section 6 | Services, team, testimonials |
| Restaurant | Section 6 (modify) | Menu, reservations, gallery |
| Blog | Section 6 (modify) | Posts, categories, authors |
| E-commerce | Custom | Products, cart, checkout |
| SaaS Landing | Custom | Features, pricing, signup |
| Package Docs | Section 7 | Markdown, code, screenshots |

---

## Key Features Included

✅ Laravel 11 + Vue 3 SPA
✅ MySQL + Redis
✅ Admin panel with auth
✅ Light/Dark theme
✅ SEO optimized
✅ Mobile responsive
✅ Docker ready
✅ Image upload (if needed)
✅ Markdown rendering (if needed)
✅ Cache management

---

## Important Notes

### Do's
✅ Be specific in your website description
✅ Include example content
✅ Specify exact validation rules
✅ Describe mobile behavior
✅ Include design preferences with hex codes
✅ List all required features

### Don'ts
❌ Be vague about requirements
❌ Skip validation rules
❌ Forget mobile considerations
❌ Leave out design preferences
❌ Assume AI knows your preferences

---

## Common Customizations

### Add a New Section
Describe it in your website idea:
```
Add a "Testimonials" section with:
- Client quote
- Client name and photo
- Company name
- Star rating (1-5)
```

### Change Colors
Specify in design preferences:
```
Dark theme:
- Primary: Navy Blue (#1e3a8a)
- Accent: Orange (#f97316)

Light theme:
- Primary: Blue (#3b82f6)
- Accent: Red (#ef4444)
```

### Add File Upload
Specify in your description:
```
Include an image gallery where admin can upload images via drag-and-drop.
Show uploaded images in a table with thumbnail, URL (with copy button), and delete option.
Store images in Laravel storage folder.
```

---

## Troubleshooting

**Issue**: Generated structure too brief
**Fix**: Reference the examples more explicitly

**Issue**: Missing features
**Fix**: Be more detailed in your description

**Issue**: Wrong design
**Fix**: Specify exact colors, fonts, and style inspiration

---

## Version Information

**System Version**: 1.0
**Last Updated**: 2026-01-27
**Compatible With**: Claude Sonnet 4.5, GPT-4, GPT-4 Turbo
**Technology**: Laravel 11, Vue 3, Tailwind CSS, MySQL 8.0, Redis, Docker

---

## Support

**Documentation**: All sections above
**Examples**: Section 6 (Agency), Section 7 (Package Docs)
**Template**: Section 5 (Blank Template)

---

**Ready to build? Follow the Quick Start guide above!** 🚀

---

# END OF DOCUMENT

This is the complete AI Website Builder system in a single file. Upload this to AI and describe your website to get started.
