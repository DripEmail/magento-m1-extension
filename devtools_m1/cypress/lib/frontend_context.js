var currentSite = null

function setCurrentFrontendSite(site) {
  currentSite = site
}

function getCurrentFrontendDomain() {
  return `http://${currentSite}.magento.localhost:3005`
}

function getCurrentFrontendSite() {
  return currentSite
}

function getCurrentFrontendWebsiteId() {
  return mapFrontendWebsiteId(currentSite)
}

function mapFrontendWebsiteId(site) {
  let websiteId = 1
  switch (site) {
    case 'main':
      websiteId = 1
      break
    case 'site1':
      websiteId = 2
      break
    default:
      throw `Unexpected site name ${site}`
  }

  return websiteId
}

export { setCurrentFrontendSite, getCurrentFrontendSite, getCurrentFrontendDomain, getCurrentFrontendWebsiteId, mapFrontendWebsiteId }