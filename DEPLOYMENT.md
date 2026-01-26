# Deployment Guide

## Prerequisites
- Node.js 14+ installed
- npm or yarn package manager
- (Production) PostgreSQL or MongoDB database

## Local Development

1. Clone the repository
```bash
git clone https://github.com/Jaysouth/caregate.git
cd caregate
```

2. Install dependencies
```bash
npm install
```

3. Create environment file
```bash
cp .env.example .env
# Edit .env and set your JWT_SECRET
```

4. Start the development server
```bash
npm run dev
```

5. Access the application
- Web Interface: http://localhost:3000
- API: http://localhost:3000/api
- Health Check: http://localhost:3000/health

## Testing

Run the test suite:
```bash
npm test
```

## Production Deployment

### Environment Variables

Required environment variables for production:

```env
NODE_ENV=production
PORT=3000
JWT_SECRET=your-secure-random-secret-key
DATABASE_URL=your-database-connection-string
```

### Database Migration

The current implementation uses in-memory storage. For production:

1. Install database driver:
```bash
npm install --save pg  # PostgreSQL
# or
npm install --save mongodb  # MongoDB
```

2. Update `server/models/database.js` to use real database
3. Run database migrations (create tables/collections)

### Deployment Options

#### Option 1: Heroku

```bash
# Install Heroku CLI
heroku login
heroku create your-app-name

# Set environment variables
heroku config:set JWT_SECRET=your-secret-key
heroku config:set NODE_ENV=production

# Deploy
git push heroku main
```

#### Option 2: AWS EC2

1. Launch an EC2 instance (Ubuntu 22.04 LTS recommended)
2. Install Node.js and npm
3. Clone repository and install dependencies
4. Use PM2 for process management:

```bash
npm install -g pm2
pm2 start server/index.js --name caregate
pm2 startup
pm2 save
```

5. Configure nginx as reverse proxy

#### Option 3: Docker

Create `Dockerfile`:
```dockerfile
FROM node:18-alpine
WORKDIR /app
COPY package*.json ./
RUN npm ci --only=production
COPY . .
EXPOSE 3000
CMD ["npm", "start"]
```

Build and run:
```bash
docker build -t caregate .
docker run -p 3000:3000 -e JWT_SECRET=your-secret caregate
```

#### Option 4: Vercel/Railway

1. Connect your GitHub repository
2. Set environment variables in the dashboard
3. Deploy with one click

### Production Checklist

- [ ] Set secure JWT_SECRET environment variable
- [ ] Configure production database
- [ ] Enable HTTPS/SSL
- [ ] Set up rate limiting
- [ ] Configure CORS for specific origins
- [ ] Set up monitoring and logging
- [ ] Configure backups
- [ ] Set up CI/CD pipeline
- [ ] Add error tracking (Sentry, etc.)
- [ ] Configure CDN for static assets
- [ ] Set up domain and DNS
- [ ] Configure email service for notifications

### Performance Optimization

1. **Enable compression**
```bash
npm install compression
```

2. **Add caching**
```bash
npm install node-cache
```

3. **Database indexing**
   - Create indexes on frequently queried fields
   - Index: userId, facilityId, shiftId, status fields

4. **Load balancing**
   - Use PM2 cluster mode
   - Or use AWS ELB/ALB

### Monitoring

Recommended monitoring tools:
- **Application**: New Relic, Datadog
- **Errors**: Sentry
- **Uptime**: UptimeRobot
- **Logs**: Papertrail, Loggly

## Scaling Considerations

### Horizontal Scaling
- Use load balancer (nginx, AWS ALB)
- Deploy multiple instances
- Use Redis for session storage
- Implement database read replicas

### Vertical Scaling
- Increase server resources
- Optimize database queries
- Implement caching strategy

### Microservices (Future)
Consider splitting into microservices:
- Auth service
- Shift service
- Booking service
- Billing service
- Notification service

## Support

For issues and questions:
- GitHub Issues: https://github.com/Jaysouth/caregate/issues
- Documentation: README.md
