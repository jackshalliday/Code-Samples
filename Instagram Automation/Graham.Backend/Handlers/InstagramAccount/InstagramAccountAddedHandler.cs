using Graham.DataAccess.Model;
using Graham.Messages.Commands.Insights;
using Graham.Messages.Commands.InstagramAccount;
using Graham.Messages.Events.InstagramAccount;
using Graham.Services;
using Graham.Static;
using NServiceBus;
using NServiceBus.Logging;
using System.Threading.Tasks;

namespace Graham.Backend.Handlers.InstagramAccount
{
    public class InstagramAccountAddedHandler : IHandleMessages<InstagramAccountAdded>
    {
        static ILog log = LogManager.GetLogger<InstagramAccountAddedHandler>();

        private readonly InstagramAccountService _service;

        public InstagramAccountAddedHandler(InstagramAccountService service)
        {
            _service = service;
        }

        public async Task Handle(InstagramAccountAdded message, IMessageHandlerContext context)
        {
            log.Info($"Received InstagramAccountAdded, InstagramAccountId = {message.InstagramAccountId}");

            if(_service.FindInstagramAccountById(message.InstagramAccountId, out var instagramAccount))
            {
                await context.Send(new ValidateInstagramAccount
                {
                    InstagramAccountId = message.InstagramAccountId,
                    Username = message.Username,
                    Password = message.Password

                });

                instagramAccount.ValidationInProgress = InstagramAccountValidationProgressValue.ValidationInProgress;
                _service.UpdateInstagramAccount(instagramAccount);

                return;
            }

            await context.Send(new SendErrorMessageToInsights
            {
                ErrorMessage = "load this message from static class"
            });
        }
    }
}
