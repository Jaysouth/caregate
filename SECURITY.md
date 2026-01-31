# Security Considerations

## Current Security Measures
- ✅ JWT-based authentication with token verification
- ✅ Password hashing with bcrypt
- ✅ Role-based access control (worker vs facility)
- ✅ Input validation on all endpoints
- ✅ Production environment checks for JWT secret

## Recommendations for Production Deployment

### Rate Limiting
All authenticated routes currently lack rate limiting. This was flagged by CodeQL security scanning.

**Recommendation**: Add rate limiting middleware using `express-rate-limit`:

```javascript
const rateLimit = require('express-rate-limit');

// General API rate limiter
const apiLimiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 100, // Limit each IP to 100 requests per windowMs
  message: 'Too many requests from this IP, please try again later.'
});

// Auth rate limiter (stricter)
const authLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 5, // 5 login attempts per 15 minutes
  message: 'Too many login attempts, please try again later.'
});

// Apply to routes
app.use('/api/', apiLimiter);
app.use('/api/auth/login', authLimiter);
```

### Additional Production Security Measures

1. **HTTPS**: Enforce HTTPS in production using helmet middleware
2. **CORS**: Configure specific allowed origins instead of allowing all
3. **Input Sanitization**: Add additional input sanitization for XSS prevention
4. **SQL Injection**: When migrating to a real database, use parameterized queries
5. **Environment Variables**: Use proper secret management (AWS Secrets Manager, etc.)
6. **Logging**: Add comprehensive logging and monitoring
7. **Session Management**: Implement refresh tokens for better security
8. **File Upload**: Add file size limits and validation if implementing document upload
9. **Error Handling**: Don't expose stack traces in production
10. **Dependencies**: Regularly update dependencies and run security audits

### Quick Production Setup

```bash
npm install --save express-rate-limit helmet express-mongo-sanitize

# Update server/index.js
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');

app.use(helmet());
app.use(rateLimit({...}));
```

## Security Summary
The current implementation is suitable for development and demonstration purposes. 
The missing rate limiting is the main security concern identified by CodeQL scanning.
All other standard security practices (authentication, authorization, password hashing) 
are properly implemented.
