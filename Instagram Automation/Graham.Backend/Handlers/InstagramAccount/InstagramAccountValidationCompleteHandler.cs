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
    public class InstagramAccountValidationCompleteHandler : IHandleMessages<InstagramAccountValidationComplete>
    {
        static ILog log = LogManager.GetLogger<InstagramAccountValidationCompleteHandler>();

        private readonly InstagramAccountService _service;

        public InstagramAccountValidationCompleteHandler(InstagramAccountService service)
        {
            _service = service;
        }

        public async Task Handle(InstagramAccountValidationComplete message, IMessageHandlerContext context)
        {
            log.Info($"Received InstagramAccountValidationComplete, InstagramAccountId = {message.InstagramAccountId}");

            if(!_service.FindInstagramAccountById(message.InstagramAccountId, out var instagramAccount))
            {
                await context.Send(new SendErrorMessageToInsights
                {
                    ErrorMessage = "load this message from static class"
                });

                return;
            }

            instagramAccount.ValidationInProgress = InstagramAccountValidationProgressValue.ValidationNotInProgress;
            instagramAccount.Validated = message.ValidationResult;

            _service.UpdateInstagramAccount(instagramAccount);
        }
    }
}
