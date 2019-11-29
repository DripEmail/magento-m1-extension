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

function getCurrentFrontendStoreViewId() {
  return mapFrontendStoreViewId(currentSite)
}

function mapFrontendWebsiteId(site) {
  let websiteId = 1
  switch (site) {
    case 'main':
      websiteId = 1
      break
    case 'site1':
      websiteId = 100
      break
    default:
      throw `Unexpected site name ${site}`
  }

  return websiteId
}


function mapFrontendStoreId(site) {
  let storeId = 0
  switch (site) {
    case 'main':
      storeId = 1
      break
    case 'site1':
      storeId = 2
      break
    default:
      throw `Unexpected site name ${site}`
  }
  return storeId
}

function mapFrontendStoreViewId(site) {
  let storeViewId = 1
  switch (site) {
    case 'main':
      storeViewId = 1
      break
    case 'site1':
      storeViewId = 300
      break
    default:
      throw `Unexpected site name ${site}`
  }
  return storeViewId
}

export { setCurrentFrontendSite, getCurrentFrontendSite, getCurrentFrontendDomain, getCurrentFrontendWebsiteId, getCurrentFrontendStoreViewId, mapFrontendWebsiteId, mapFrontendStoreId }
