module.exports = {
  monitor : [
    {
    name        : "status",
    script      : "/opt/sources/boxmls/service-rets-api/status.js",
    watch       : false
  },
    {
    name       : "hhvm",
    interpreter : "/usr/bin/hhvm",
    interpreter_args: "--mode server -vServer.Type=proxygen -vServer.Port=8000 -vServer.AllowRunAsRoot=1",
    exec_mode  : "fork"
  }]
}
