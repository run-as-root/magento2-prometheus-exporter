# GitHub Actions Workflows

This directory contains comprehensive GitHub Actions workflows for the Magento 2 Prometheus Exporter project. These workflows ensure code quality, automate releases, and maintain project health.

## 🔄 Workflows Overview

### 1. CI (Continuous Integration) - `ci.yml`

**Trigger**: Push/PR to `master` or `develop` branches

**Purpose**: Core testing and quality assurance pipeline

**Jobs**:
- **PHP CS Fixer**: Code style validation and automatic fixing
- **PHPStan**: Static analysis for type safety and code quality
- **Unit Tests**: Runs across PHP 7.4, 8.0, 8.1, 8.2 with coverage reporting
- **Integration Tests**: Tests against Magento 2.4.5 and 2.4.6
- **Compatibility Check**: Validates Magento coding standards and PHP compatibility
- **Security Check**: Scans for security vulnerabilities
- **Composer Validation**: Ensures composer.json integrity

### 2. Release - `release.yml`

**Trigger**: Git tags (`v*.*.*`) or manual dispatch

**Purpose**: Automated release management and distribution

**Jobs**:
- **Validate Release**: Checks version format and changelog consistency
- **Run Tests**: Final test suite before release
- **Create Release**: Generates GitHub release with archives
- **Update Packagist**: Notifies Packagist of new version
- **Post Release**: Updates development branch versions

### 3. Code Quality - `code-quality.yml`

**Trigger**: Push/PR to main branches, weekly schedule

**Purpose**: Comprehensive code quality analysis

**Jobs**:
- **PHP CS Fixer**: Advanced code style enforcement
- **PHPStan**: Deep static analysis
- **Psalm**: Additional static analysis tool
- **Magento Coding Standard**: Magento-specific code standards
- **PHP Compatibility**: Multi-version PHP compatibility check
- **PHPMD**: Mess detection for code smells
- **PHPCPD**: Copy/paste detection
- **Security Checker**: Vulnerability scanning
- **Dependency Check**: Unused dependency detection
- **Code Metrics**: Lines of code analysis

### 4. Dependency Update - `dependency-update.yml`

**Trigger**: Weekly schedule (Mondays 9 AM UTC) or manual

**Purpose**: Automated dependency management and security monitoring

**Jobs**:
- **Update Dependencies**: Creates PRs for patch/minor updates
- **Security Audit**: Scans for security vulnerabilities
- **Composer Audit**: Official Composer security advisories
- **Outdated Check**: Reports on outdated packages
- **Cleanup**: Removes old dependency update PRs

### 5. Documentation - `docs.yml`

**Trigger**: Changes to docs, README, or source code

**Purpose**: Documentation validation and generation

**Jobs**:
- **Validate Docs**: Markdown linting and link checking
- **Generate API Docs**: Creates PHPDoc documentation
- **Check Changelog**: Ensures changelog updates in PRs
- **Validate Changelog**: Checks changelog format
- **Spell Check**: Spell checking with custom dictionary
- **Update Docs**: Auto-generates metrics and config documentation
- **Deploy Docs**: Publishes to GitHub Pages

### 6. Performance Monitoring - `performance.yml`

**Trigger**: Push/PR to main branches, weekly schedule

**Purpose**: Performance testing and monitoring

**Jobs**:
- **Performance Baseline**: Measures core performance metrics
- **Performance Comparison**: Compares PR performance vs base branch
- **Load Testing**: High-load scenario testing with k6

## 🛠️ Configuration

### Required Secrets

Add these secrets in your GitHub repository settings:

- `GITHUB_TOKEN`: Automatically provided by GitHub
- `PACKAGIST_TOKEN`: For automated Packagist updates (optional)

### Required Permissions

Ensure the following permissions are enabled for GitHub Actions:

- **Contents**: Read/Write (for creating releases and updating docs)
- **Issues**: Write (for creating security issue reports)
- **Pull Requests**: Write (for dependency update PRs)
- **Pages**: Write (for documentation deployment)

## 📊 Workflow Features

### Automated Features

- ✅ **Code Style**: Automatic code formatting on develop branch
- 🔄 **Dependency Updates**: Weekly automated dependency PRs
- 📚 **Documentation**: Auto-generated API docs and metrics lists
- 🏷️ **Releases**: Fully automated release process with archives
- 🔍 **Security**: Continuous vulnerability monitoring
- 📈 **Performance**: Regular performance regression testing

### Quality Gates

- **Unit Tests**: Must pass across all PHP versions
- **Integration Tests**: Must pass against supported Magento versions
- **Security**: No critical vulnerabilities allowed
- **Code Style**: Must follow PSR-12 and Magento standards
- **Static Analysis**: PHPStan level 5 compliance required

### Reporting

All workflows generate detailed reports in the GitHub Actions summary, including:

- Test coverage metrics
- Performance benchmarks
- Security scan results
- Code quality scores
- Dependency status

## 🚀 Getting Started

1. **Fork/Clone** this repository
2. **Enable Actions** in your repository settings
3. **Add Secrets** (if needed for Packagist integration)
4. **Push Changes** to trigger the workflows

## 📋 Workflow Status

You can monitor workflow status through:

- **GitHub Actions Tab**: Real-time workflow execution
- **README Badges**: Add status badges to your README
- **Branch Protection**: Configure required status checks

### Example Badges

Add these to your README.md:

```markdown
![CI](https://github.com/your-username/magento2-prometheus-exporter/workflows/CI/badge.svg)
![Code Quality](https://github.com/your-username/magento2-prometheus-exporter/workflows/Code%20Quality/badge.svg)
![Security](https://github.com/your-username/magento2-prometheus-exporter/workflows/Dependency%20Update/badge.svg)
```

## 🔧 Customization

### Modifying Workflows

- **PHP Versions**: Update matrix in `ci.yml` and `release.yml`
- **Magento Versions**: Update matrix in integration test jobs
- **Schedule**: Modify cron expressions for scheduled workflows
- **Quality Gates**: Adjust PHPStan levels, coverage thresholds

### Adding Custom Checks

1. Create new jobs in existing workflows
2. Add custom scripts in `.github/scripts/`
3. Use marketplace actions for additional functionality

## 🆘 Troubleshooting

### Common Issues

- **Composer Auth**: May need Magento marketplace credentials
- **Memory Limits**: Increase PHP memory for large projects
- **Timeouts**: Adjust timeout values for slow operations
- **Permissions**: Ensure proper GitHub token permissions

### Debug Mode

Enable debug logging by setting repository variable:
- `ACTIONS_STEP_DEBUG`: `true`
- `ACTIONS_RUNNER_DEBUG`: `true`

## 🤝 Contributing

When contributing to workflows:

1. Test changes in a fork first
2. Update documentation for new features
3. Follow existing naming conventions
4. Add appropriate error handling

## 📚 Additional Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Magento DevDocs](https://devdocs.magento.com/)
- [Prometheus Documentation](https://prometheus.io/docs/)
- [PHP Testing Best Practices](https://phpunit.de/documentation.html)

---

*These workflows are designed to be production-ready and can be customized based on your specific requirements.*