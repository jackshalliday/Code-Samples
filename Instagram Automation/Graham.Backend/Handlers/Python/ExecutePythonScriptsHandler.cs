using Graham.Messages.Commands.Python;
using Graham.Messages.Events.InstagramAccount;
using NServiceBus;
using NServiceBus.Logging;
using System;
using System.Collections.Generic;
using System.Text;
using System.Threading.Tasks;

namespace Graham.Backend.Handlers.Python
{
    class ExecutePythonScriptsHandler : IHandleMessages<ExecuteLoginScript>
    {
        static ILog log = LogManager.GetLogger<ExecutePythonScriptsHandler>();

        public async Task Handle(ExecuteLoginScript message, IMessageHandlerContext context)
        {
            log.Info($"Received InstagramAccountAdded, InstagramAccountId = {message.InstagramAccountId}");

            //logic to be added here
            var loginSuccessful = true;

            if(loginSuccessful)
            {
                await context.Publish(new InstagramAccountLoginSuccessful
                {
                    InstagramAccountId = message.InstagramAccountId

                });

                return;
            }

            await context.Publish(new InstagramAccountLoginFailed
            {
                InstagramAccountId = message.InstagramAccountId

            });
        }
    }
}
