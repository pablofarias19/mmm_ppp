# FASE 4: Noticias/Artículos - Setup Guide

## 📋 Summary

FASE 4 (Noticias/Artículos) has been successfully implemented following the same architecture pattern as Encuestas, Eventos, and Trivias. This system allows administrators to create, manage, and publish news articles/news with professional presentation.

---

## 🗂️ Files Created

### 1. **Model** (`/models/Noticia.php`)
- Handles all database operations for noticias
- Methods:
  - `getAllActive()` - Get all published articles
  - `getAll()` - Get all articles (including draft/inactive)
  - `getById($id)` - Get single article by ID
  - `getByCategoria($categoria)` - Filter by category
  - `getRecent($limit)` - Get recent articles (for widget)
  - `getByAutor($user_id)` - Get articles by author
  - `getStats()` - Get statistics (total, by category, total views)
  - `create($data)` - Create new article
  - `update($id, $data)` - Update article
  - `deactivate($id)` - Unpublish article (soft delete)
  - `activate($id)` - Publish article
  - `incrementVistas($id)` - Track views
  - `delete($id)` - Permanently delete article
  - `uploadImage($file)` - Handle image uploads

### 2. **API Endpoint** (`/api/noticias.php`)
RESTful endpoints for noticias management:

```
GET  /api/noticias.php                           → Get all active articles
GET  /api/noticias.php?id=X                      → Get single article
GET  /api/noticias.php?action=recent&limit=N    → Get recent articles
GET  /api/noticias.php?action=categoria&cat=X   → Filter by category
GET  /api/noticias.php?action=stats              → Get statistics

POST /api/noticias.php?action=create             → Create new article (admin)
POST /api/noticias.php?action=update             → Update article (admin)
POST /api/noticias.php?action=delete             → Delete article (admin)
POST /api/noticias.php?action=toggle             → Publish/unpublish (admin)
POST /api/noticias.php?action=view               → Register view (any user)
```

### 3. **Admin Dashboard** (`/admin/noticias/dashboard.php`)
Professional admin interface with:
- 📊 Statistics cards (active, inactive, total views)
- ✍️ Article creation form with:
  - Title, content (textarea with rich formatting ready)
  - Category selector (General, Negocios, Marcas, Eventos, Tendencias, Educación)
  - Image upload with drag-and-drop support
  - Publish immediately checkbox
- 📋 Article listing with:
  - Title, category, views count
  - Status badge (Active/Inactive)
  - Publish/unpublish button
  - Delete button with confirmation
  - Hover effects and responsive design

### 4. **Map Widget** (integrated in `/views/business/map.php`)
- Widget container: `#noticias-container`
- Shows "Últimas Noticias" (Latest News) in sidebar
- Displays 5 most recent articles with:
  - Title (truncated)
  - Category with icon (📁)
  - View count
  - Hover animations and click handlers
- Modal popup on article click:
  - Full title and metadata
  - Cover image (if available)
  - Article category, views, publication date
  - Truncated content preview
  - "Leer Completo" (Read Full) button
  - View counter automatically incremented

### 5. **Database Migration** (`/migrations/001_create_noticias_table.sql`)
Table schema with the following fields:
```sql
CREATE TABLE noticias (
  id INT PRIMARY KEY AUTO_INCREMENT,
  titulo VARCHAR(255) NOT NULL,
  contenido LONGTEXT NOT NULL,
  imagen VARCHAR(255),
  categoria VARCHAR(100) DEFAULT 'General',
  user_id INT,
  vistas INT DEFAULT 0,
  activa BOOLEAN DEFAULT 1,
  fecha_publicacion DATETIME,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  INDEX idx_activa (activa),
  INDEX idx_fecha (fecha_publicacion),
  INDEX idx_categoria (categoria),
  INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
```

---

## 🚀 Setup Instructions

### Step 1: Create Database Table
Run the SQL migration to create the noticias table:

**Option A: Using phpMyAdmin**
1. Go to phpMyAdmin
2. Select your Mapita database
3. Click "SQL" tab
4. Copy and paste the contents of `/migrations/001_create_noticias_table.sql`
5. Click "Go"

**Option B: Using Command Line**
```bash
mysql -u user -p database_name < migrations/001_create_noticias_table.sql
```

### Step 2: Create Upload Directory
The system needs a directory for article images:
```bash
mkdir -p /uploads/noticias
chmod 755 /uploads/noticias
```

### Step 3: Access Admin Dashboard
1. Log in as admin at `/login`
2. Navigate to `Admin → 📰 Noticias`
3. Start creating articles!

---

## 🎨 Design & Features

### Color Scheme
- Primary Color: `#667eea` (Purple/Blue)
- Secondary: `#764ba2` (Dark Purple)
- Gradient: `linear-gradient(135deg, #667eea 0%, #764ba2 100%)`
- Widget Border: Left border `4px solid #667eea`

### User Experience
- **Article Creation**: Intuitive form with optional cover image
- **Management**: List view with bulk actions (publish/delete)
- **Discovery**: Widget shows 5 latest articles with category filtering
- **Engagement**: View counting tracks article popularity
- **Status**: Visual badges (Active/Inactive) for quick identification

### Responsive Design
- Mobile-first approach
- Grid layout adapts from 2 columns to 1 on mobile
- Touch-friendly buttons and inputs
- Modal dialogs center properly on all screen sizes

---

## 📊 Statistics Tracked

- Total active articles
- Total inactive articles  
- Total views across all articles
- Articles grouped by category
- Per-article view count

---

## 🔗 Integration Points

### Admin Dashboard Link
Added to `/admin/dashboard.php`:
```php
<a href="/admin/noticias/dashboard.php">📰 Noticias</a>
```

### Map Sidebar Widget
- Loads automatically when map loads
- Shows in responsive sidebar
- Fetches 5 recent active articles
- Updates incrementally without full page reload

### No Breaking Changes
- All existing functionality (Encuestas, Eventos, Trivias, Brands) remains intact
- Independent API endpoints don't conflict
- Separate database table and file structure

---

## 📝 Categories

Pre-configured categories (can be expanded):
- **General** - General news
- **Negocios** - Business-related news
- **Marcas** - Brand news and updates
- **Eventos** - Event announcements
- **Tendencias** - Trends and insights
- **Educación** - Educational content

---

## 🔐 Security Features

- Admin-only article creation/deletion
- User authentication required for modifications
- CSRF token protection on all forms
- SQL injection prevention (prepared statements)
- File type validation for image uploads
- Soft delete via deactivation (recovery possible)

---

## 📱 Next Steps (Optional Future Enhancements)

1. **Rich Text Editor**: Integrate editor like Quill or TinyMCE for WYSIWYG article editing
2. **Comments**: Allow users to comment on articles
3. **Tags**: Add flexible tagging system beyond categories
4. **Search**: Full-text search in article titles and content
5. **Social Sharing**: Built-in share buttons (Twitter, Facebook, WhatsApp)
6. **RSS Feed**: Generate RSS feed for article subscriptions
7. **Author Profiles**: Show article author information
8. **Featured Articles**: Pin important articles to top
9. **Article Scheduling**: Publish articles at future dates
10. **Analytics**: Detailed view analytics and engagement metrics

---

## 🎯 Summary

**FASE 4 Status**: ✅ COMPLETE

All components have been implemented and integrated:
- ✅ Model (Noticia.php)
- ✅ API (/api/noticias.php)
- ✅ Admin Dashboard (/admin/noticias/dashboard.php)
- ✅ Map Widget (integrated in map.php)
- ✅ Database Schema (migrations/001_create_noticias_table.sql)
- ✅ Admin Link (in admin/dashboard.php)

The noticias system follows the exact same architecture pattern as Encuestas, Eventos, and Trivias, ensuring consistency and maintainability across the Mapita platform.

---

## 📞 Support

For questions or issues:
1. Check `/api/noticias.php` response codes
2. Verify database table creation: `DESCRIBE noticias`
3. Check upload directory permissions
4. Review browser console for JavaScript errors
5. Check server error logs for PHP errors
