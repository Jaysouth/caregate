// In-memory database for demo purposes
// In production, this would use a real database like PostgreSQL or MongoDB

class Database {
  constructor() {
    this.users = new Map();
    this.facilities = new Map();
    this.shifts = new Map();
    this.bookings = new Map();
    this.timesheets = new Map();
    this.invoices = new Map();
    this.complianceRecords = new Map();
  }

  // Generic CRUD operations
  create(collection, id, data) {
    this[collection].set(id, { id, ...data, createdAt: new Date(), updatedAt: new Date() });
    return this[collection].get(id);
  }

  read(collection, id) {
    return this[collection].get(id);
  }

  update(collection, id, data) {
    const existing = this[collection].get(id);
    if (!existing) return null;
    const updated = { ...existing, ...data, updatedAt: new Date() };
    this[collection].set(id, updated);
    return updated;
  }

  delete(collection, id) {
    return this[collection].delete(id);
  }

  list(collection, filter = null) {
    const items = Array.from(this[collection].values());
    if (!filter) return items;
    
    return items.filter(item => {
      return Object.keys(filter).every(key => {
        if (Array.isArray(filter[key])) {
          return filter[key].includes(item[key]);
        }
        return item[key] === filter[key];
      });
    });
  }

  search(collection, predicate) {
    return Array.from(this[collection].values()).filter(predicate);
  }
}

// Singleton instance
const db = new Database();

module.exports = db;
