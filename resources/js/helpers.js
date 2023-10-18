// https://youmightnotneed.com/lodash#get
const get = (obj, path, defValue) => {
    if (!path) return undefined
    const pathArray = Array.isArray(path) ? path : path.match(/([^[.\]])+/g)
    const result = pathArray.reduce(
        (prevObj, key) => prevObj && prevObj[key],
        obj
    )
    return result === undefined ? defValue : result
}

export { get }